<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\Geocoding\GeocodingServiceInterface;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\Address\AddressFormatterInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;

class GeocodingOfferCdbXmlProjector extends AbstractCdbXmlProjector
{
    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $documentFactory;

    /**
     * @var OfferRelationsServiceInterface
     */
    private $offerRelationsService;

    /**
     * @var AddressFormatterInterface
     */
    private $addressFormatter;

    /**
     * @var GeocodingServiceInterface
     */
    private $geocodingService;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $documentFactory
     * @param OfferRelationsServiceInterface $offerRelationsService
     * @param AddressFormatterInterface $addressFormatter
     * @param GeocodingServiceInterface $geocodingService
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $documentFactory,
        OfferRelationsServiceInterface $offerRelationsService,
        AddressFormatterInterface $addressFormatter,
        GeocodingServiceInterface $geocodingService
    ) {
        parent::__construct($documentRepository);
        $this->documentFactory = $documentFactory;
        $this->offerRelationsService = $offerRelationsService;
        $this->addressFormatter = $addressFormatter;
        $this->geocodingService = $geocodingService;
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return [
            PlaceCreated::class => 'applyPlaceAddressUpdated',
            PlaceMajorInfoUpdated::class => 'applyPlaceAddressUpdated',
            EventCreated::class => 'applyEventAddressUpdated',
            EventMajorInfoUpdated::class => 'applyEventAddressUpdated',
        ];
    }

    /**
     * @param PlaceCreated|PlaceMajorInfoUpdated $event
     * @return \Generator|CdbXmlDocument[]
     */
    protected function applyPlaceAddressUpdated($event)
    {
        $placeId = $event->getPlaceId();
        $address = $event->getAddress();

        yield $this->getCdbXmlDocumentWithUpdatedAddressCoordinates($placeId, $address);

        foreach ($this->getEventIdsRelatedToPlace($placeId) as $eventId) {
            yield $this->getCdbXmlDocumentWithUpdatedAddressCoordinates($eventId, $address);
        }
    }

    /**
     * @param EventCreated|EventMajorInfoUpdated $event
     * @return \Generator|CdbXmlDocument[]
     */
    protected function applyEventAddressUpdated($event)
    {
        $eventId = $event->getEventId();

        $address = new Address(
            $event->getLocation()->getStreet(),
            $event->getLocation()->getPostalcode(),
            $event->getLocation()->getLocality(),
            $event->getLocation()->getCountry()
        );

        yield $this->getCdbXmlDocumentWithUpdatedAddressCoordinates($eventId, $address);
    }

    /**
     * @param $placeId
     * @return string[]
     */
    private function getEventIdsRelatedToPlace($placeId)
    {
        return $this->offerRelationsService->getByPlace($placeId);
    }

    /**
     * @param $id
     * @param $address
     * @return CdbXmlDocument
     */
    private function getCdbXmlDocumentWithUpdatedAddressCoordinates($id, $address)
    {
        $coordinates = $this->geocodingService->getCoordinates(
            $this->addressFormatter->format($address)
        );

        $cdbGeoInformation = new \CultureFeed_Cdb_Data_Address_GeoInformation(
            (string) $coordinates->getLongitude()->toDouble(),
            (string) $coordinates->getLatitude()->toDouble()
        );

        $cdbXmlDocument = $this->getCdbXmlDocument($id);

        $this->logger->info("cdbxml document found ({$id})");

        try {
            $cdbItem = ActorItemFactory::createActorFromCdbXml(
                \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3'),
                $cdbXmlDocument->getCdbXml()
            );

            $this->logger->info("cdbxml document is actor ({$id})");
        } catch (\CultureFeed_Cdb_ParseException $e) {
            $cdbItem = EventItemFactory::createEventFromCdbXml(
                \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3'),
                $cdbXmlDocument->getCdbXml()
            );

            $this->logger->info("cdbxml document is event ({$id})");

            if (!empty($cdbItem->getLocation()) &&
                !empty($cdbItem->getLocation()->getAddress()) &&
                !empty($cdbItem->getLocation()->getAddress()->getPhysicalAddress())) {
                $cdbLocation = $cdbItem->getLocation();
                $cdbLocationAddress = $cdbLocation->getAddress();
                $cdbLocationPhysicalAddress = $cdbLocationAddress->getPhysicalAddress();
                $cdbLocationPhysicalAddress->setGeoInformation($cdbGeoInformation);
                $cdbLocationAddress->setPhysicalAddress($cdbLocationPhysicalAddress);
                $cdbLocation->setAddress($cdbLocationAddress);
                $cdbItem->setLocation($cdbLocation);
            }
        }

        $cdbContactInfo = $cdbItem->getContactInfo();
        $cdbAddresses = $cdbContactInfo->getAddresses();

        if (empty($cdbAddresses)) {
            $this->logger->error("no address found in event contactinfo ({$id})");
            return $cdbXmlDocument;
        }

        /* @var \CultureFeed_Cdb_Data_Address $cdbAddress */
        $cdbAddress = $cdbAddresses[0];
        $cdbPhysicalAddress = $cdbAddress->getPhysicalAddress();

        if (empty($cdbPhysicalAddress)) {
            $this->logger->error("no physical address found in event address ({$id})");
            return $cdbXmlDocument;
        }

        $cdbPhysicalAddress->setGeoInformation($cdbGeoInformation);
        $cdbAddress->setPhysicalAddress($cdbPhysicalAddress);

        foreach ($cdbContactInfo->getAddresses() as $index => $cdbContactInfoAddress) {
            $cdbContactInfo->removeAddress($index);
        }

        $cdbContactInfo->addAddress($cdbAddress);
        $cdbItem->setContactInfo($cdbContactInfo);

        $cdbXmlDocument = $this->documentFactory->fromCulturefeedCdbItem($cdbItem);

        return $cdbXmlDocument;
    }
}
