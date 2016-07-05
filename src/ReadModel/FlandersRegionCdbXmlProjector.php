<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;

/**
 * Class FlandersRegionCdbXmlProjector
 * This projector takes UDB3 domain messages, projects additional
 * flandersregion categories to CdbXml and then
 * publishes the changes to a public URL.
 */
class FlandersRegionCdbXmlProjector extends FlandersRegionAbstractCdbXmlProjector
{
    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;


    /**
     * FlandersRegionCdbXmlProjector constructor.
     *
     * @param \CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface $documentRepository
     * @param \CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
    ) {
        parent::__construct($documentRepository);

        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
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
            OrganizerCreated::class => 'applyFlandersRegionOrganizerCreatedImportedUpdated',
            OrganizerImportedFromUDB2::class => 'applyFlandersRegionOrganizerCreatedImportedUpdated',
            OrganizerUpdatedFromUDB2::class => 'applyFlandersRegionOrganizerCreatedImportedUpdated',
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

        $category = $this->findFlandersRegion($physicalAddress);
        $this->updateFlandersRegionCategories($event, $category);

        // Return a new CdbXmlDocument.
        return array(
            $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($event)
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

        $category = $this->findFlandersRegion($physicalAddress);
        $this->updateFlandersRegionCategories($place, $category);

        // Return a new CdbXmlDocument.
        return array(
            $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($place)
        );
    }

    /**
     * @param OrganizerCreated | OrganizerImportedFromUDB2 | OrganizerUpdatedFromUDB2 $payload
     *
     * @return CdbXmlDocument[]
     */
    public function applyFlandersRegionOrganizerCreatedImportedUpdated($payload)
    {
        $organizerCdbXml = $this->getCdbXmlDocument(
            (get_class($payload) == OrganizerCreated::class) ? $payload->getOrganizerId() : $payload->getActorId()
        );

        $organizer = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $organizerCdbXml->getCdbXml()
        );

        $contactInfo = $organizer->getContactInfo();
        $addresses = $contactInfo->getAddresses();
        /* @var \CultureFeed_Cdb_Data_Address $address */
        $address = $addresses[0];
        $physicalAddress = $address->getPhysicalAddress();

        $category = $this->findFlandersRegion($physicalAddress);
        $this->updateFlandersRegionCategories($organizer, $category);

        // Return a new CdbXmlDocument.
        return array(
            $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($organizer)
        );
    }
}
