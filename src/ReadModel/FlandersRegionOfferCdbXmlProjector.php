<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\FlandersRegionCategoryServiceInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\PlaceEvent;

class FlandersRegionOfferCdbXmlProjector extends AbstractCdbXmlProjector
{
    /**
     * @var FlandersRegionCategoryServiceInterface
     */
    private $categories;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param FlandersRegionCategoryServiceInterface $categories
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        FlandersRegionCategoryServiceInterface $categories
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
        $eventId = $this->determineOfferId($payload);

        $eventCdbXmlDocument = $this->getCdbXmlDocument($eventId);

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXmlDocument->getCdbXml()
        );

        $location = $event->getLocation();

        if (empty($location)) {
            $this->logger->error("no location found in event ({$eventId})");
            return;
        }

        $address = $location->getAddress();

        if (empty($address)) {
            $this->logger->error("no address found in event ({$eventId})");
            return;
        }

        $physicalAddress = $address->getPhysicalAddress();

        if (empty($physicalAddress)) {
            $this->logger->error("no physical address found in event address ({$eventId})");
            return;
        }

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
        $placeId = $this->determineOfferId($payload);

        $placeCdbXmlDocument = $this->getCdbXmlDocument($placeId);

        $place = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXmlDocument->getCdbXml()
        );

        $contactInfo = $place->getContactInfo();

        if (empty($contactInfo)) {
            $this->logger->error("no contactinfo found in place ({$placeId})");
            return;
        }

        $addresses = $contactInfo->getAddresses();

        if (empty($addresses)) {
            $this->logger->error("no address found in place contactinfo ({$placeId})");
            return;
        }

        /* @var \CultureFeed_Cdb_Data_Address $address */
        $address = $addresses[0];

        $physicalAddress = $address->getPhysicalAddress();

        if (empty($physicalAddress)) {
            $this->logger->error("no physical address found in place address ({$placeId})");
            return;
        }

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
