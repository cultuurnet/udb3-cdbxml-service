<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\PlaceEvent;

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
            EventCreated::class => 'applyFlandersRegionToEvent',
            PlaceCreated::class => 'applyFlandersRegionToPlace',
            EventMajorInfoUpdated::class => 'applyFlandersRegionToEvent',
            PlaceMajorInfoUpdated::class => 'applyFlandersRegionToPlace',
        ];
    }

    /**
     * @param EventCreated | EventMajorInfoUpdated $payload
     *
     * @return \Generator|CdbXmlDocument[]
     */
    public function applyFlandersRegionToEvent($payload)
    {
        $eventCdbXml = $this->getCdbXmlDocument(
            $this->determineOfferId($payload)
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
        yield $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($event);
    }

    /**
     * @param PlaceCreated | PlaceMajorInfoUpdated $payload
     *
     * @return \Generator|CdbXmlDocument[]
     */
    public function applyFlandersRegionToPlace($payload)
    {
        $placeCdbXml = $this->getCdbXmlDocument(
            $this->determineOfferId($payload)
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
        yield $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($place);

    }

    /**
     * @param mixed $event
     * @return string
     */
    private function determineOfferId($event)
    {
        if ($event instanceof EventEvent) {
            return $event->getEventId();
        } elseif ($event instanceof PlaceEvent) {
            return $event->getPlaceId();
        } elseif ($event instanceof AbstractEvent) {
            return $event->getItemId();
        } else {
            throw new \InvalidArgumentException('Could not determine offer id from ' . get_class($event));
        }
    }
}
