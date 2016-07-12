<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;

/**
 * Class FlandersRegionOfferCdbXmlProjector
 * This projector takes UDB3 domain messages, projects additional
 * flandersregion categories to CdbXml and then
 * publishes the changes to a public URL.
 */
class FlandersRegionOfferCdbXmlProjector extends FlandersRegionAbstractCdbXmlProjector
{
    /**
     * @var FlandersRegionCategories
     */
    private $categories;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;


    /**
     * FlandersRegionCdbXmlProjector constructor.
     *
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param FlandersRegionCategories $categories
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        FlandersRegionCategories $categories
    ) {
        parent::__construct($documentRepository);

        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->categories = $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlers()
    {
        return [
            EventCreated::class => 'applyFlandersRegionEventAddedUpdated',
            PlaceCreated::class => 'applyFlandersRegionPlaceAddedUpdated',
            EventMajorInfoUpdated::class => 'applyFlandersRegionEventAddedUpdated',
            PlaceMajorInfoUpdated::class => 'applyFlandersRegionPlaceAddedUpdated',
        ];
    }

    /**
     * @param EventCreated | EventMajorInfoUpdated $payload
     *
     * @return CdbXmlDocument[]
     */
    public function applyFlandersRegionEventAddedUpdated($payload)
    {
        $eventCdbXml = $this->getCdbXmlDocument(
            (get_class($payload) == EventMajorInfoUpdated::class) ? $payload->getItemId() : $payload->getEventId()
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $location = $event->getLocation();
        $address = $location->getAddress();
        $physicalAddress = $address->getPhysicalAddress();

        $category = $this->categories->findFlandersRegionCategory($physicalAddress);
        $this->categories->updateFlandersRegionCategories($event, $category);

        // Return a new CdbXmlDocument.
        return array(
            $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($event),
        );
    }

    /**
     * @param PlaceCreated | PlaceMajorInfoUpdated $payload
     *
     * @return CdbXmlDocument[]
     */
    public function applyFlandersRegionPlaceAddedUpdated($payload)
    {
        $placeCdbXml = $this->getCdbXmlDocument(
            $payload->getPlaceId()
        );

        $place = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXml->getCdbXml()
        );

        $contactInfo = $place->getContactInfo();
        $addresses = $contactInfo->getAddresses();
        /* @var \CultureFeed_Cdb_Data_Address $address */
        $address = $addresses[0];
        $physicalAddress = $address->getPhysicalAddress();

        $category = $this->categories->findFlandersRegionCategory($physicalAddress);
        $this->categories->updateFlandersRegionCategories($place, $category);

        // Return a new CdbXmlDocument.
        return array(
            $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($place),
        );
    }
}
