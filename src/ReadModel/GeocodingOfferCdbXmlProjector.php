<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated as EventGeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated as PlaceGeoCoordinatesUpdated;

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
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $documentFactory
     * @param OfferRelationsServiceInterface $offerRelationsService
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $documentFactory,
        OfferRelationsServiceInterface $offerRelationsService
    ) {
        parent::__construct($documentRepository);
        $this->documentFactory = $documentFactory;
        $this->offerRelationsService = $offerRelationsService;
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return [
            EventGeoCoordinatesUpdated::class => 'applyEventGeoCoordinatesUpdated',
            PlaceGeoCoordinatesUpdated::class => 'applyPlaceGeoCoordinatesUpdated',
        ];
    }

    /**
     * @param EventGeoCoordinatesUpdated $geoCoordinatesUpdated
     * @return \Generator|CdbXmlDocument[]
     */
    protected function applyEventGeoCoordinatesUpdated(EventGeoCoordinatesUpdated $geoCoordinatesUpdated)
    {
        yield $this->getCdbXmlDocumentWithUpdatedGeoCoordinates(
            $geoCoordinatesUpdated->getItemId(),
            $geoCoordinatesUpdated->getCoordinates()
        );
    }

    /**
     * @param PlaceGeoCoordinatesUpdated $geoCoordinatesUpdated
     * @return \Generator|CdbXmlDocument[]
     */
    protected function applyPlaceGeoCoordinatesUpdated(PlaceGeoCoordinatesUpdated $geoCoordinatesUpdated)
    {
        yield $this->getCdbXmlDocumentWithUpdatedGeoCoordinates(
            $geoCoordinatesUpdated->getItemId(),
            $geoCoordinatesUpdated->getCoordinates()
        );

        foreach ($this->offerRelationsService->getByPlace($geoCoordinatesUpdated->getItemId()) as $eventId) {
            yield $this->getCdbXmlDocumentWithUpdatedGeoCoordinates(
                $eventId,
                $geoCoordinatesUpdated->getCoordinates()
            );
        }
    }

    /**
     * @param string $id
     * @param Coordinates $coordinates
     * @return CdbXmlDocument
     */
    private function getCdbXmlDocumentWithUpdatedGeoCoordinates($id, Coordinates $coordinates)
    {
        $cdbGeoInformation = new \CultureFeed_Cdb_Data_Address_GeoInformation(
            (string) $coordinates->getLongitude()->toDouble(),
            (string) $coordinates->getLatitude()->toDouble()
        );

        $cdbXmlDocument = $this->getCdbXmlDocument($id);

        try {
            $cdbItem = ActorItemFactory::createActorFromCdbXml(
                \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3'),
                $cdbXmlDocument->getCdbXml()
            );
        } catch (\CultureFeed_Cdb_ParseException $e) {
            $cdbItem = EventItemFactory::createEventFromCdbXml(
                \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3'),
                $cdbXmlDocument->getCdbXml()
            );

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
