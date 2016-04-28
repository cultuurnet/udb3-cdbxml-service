<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultureFeed_Cdb_Data_Address;
use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultureFeed_Cdb_Data_Calendar_BookingPeriod;
use CultureFeed_Cdb_Data_Calendar_OpeningTime;
use CultureFeed_Cdb_Data_Calendar_Period;
use CultureFeed_Cdb_Data_Calendar_PeriodList;
use CultureFeed_Cdb_Data_Calendar_Permanent;
use CultureFeed_Cdb_Data_Calendar_SchemeDay;
use CultureFeed_Cdb_Data_Calendar_Timestamp;
use CultureFeed_Cdb_Data_Calendar_TimestampList;
use CultureFeed_Cdb_Data_Calendar_Weekscheme;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Data_CategoryList;
use CultureFeed_Cdb_Data_ContactInfo;
use CultureFeed_Cdb_Data_EventDetail;
use CultureFeed_Cdb_Data_EventDetailList;
use CultureFeed_Cdb_Data_Keyword;
use CultureFeed_Cdb_Data_Location;
use CultureFeed_Cdb_Data_Mail;
use CultureFeed_Cdb_Data_Phone;
use CultureFeed_Cdb_Data_Url;
use CultureFeed_Cdb_Item_Base;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\Media\EditImageTrait;
use CultuurNet\UDB3\CdbXmlService\NullCdbXmlPublisher;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated as EventBookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated as EventContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated as EventDescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved as EventImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated as EventImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelDeleted as EventLabelDeleted;
use CultuurNet\UDB3\Event\Events\MainImageSelected as EventMainImageSelected;
use CultuurNet\UDB3\Event\Events\TitleTranslated as EventTitleTranslated;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted as EventOrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated as EventOrganizerUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated as EventTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted as EventTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated as PlaceBookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated as PlaceContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated as PlaceDescriptionTranslated;
use CultuurNet\UDB3\Place\Events\ImageAdded as PlaceImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved as PlaceImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated as PlaceImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelDeleted as PlaceLabelDeleted;
use CultuurNet\UDB3\Place\Events\MainImageSelected as PlaceMainImageSelected;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\TitleTranslated as PlaceTitleTranslated;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted as PlaceOrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated as PlaceOrganizerUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated as PlaceTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted as PlaceTypicalAgeRangeDeleted;
use DateTime;

/**
 * Class OfferToEventCdbXmlProjector
 * This projector takes UDB3 domain messages, projects them to CdbXml and then
 * publishes the changes to a public URL.
 */
class OfferToEventCdbXmlProjector implements EventListenerInterface
{
    use EditImageTrait;
    
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var MetadataCdbItemEnricherInterface
     */
    private $metadataCdbItemEnricher;

    /**
     * @var CdbXmlPublisherInterface
     */
    private $cdbXmlPublisher;

    /**
     * @var DocumentRepositoryInterface
     */
    private $actorDocumentRepository;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        MetadataCdbItemEnricherInterface $metadataCdbItemEnricher,
        DocumentRepositoryInterface $actorDocumentRepository
    ) {
        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->metadataCdbItemEnricher = $metadataCdbItemEnricher;
        $this->cdbXmlPublisher = new NullCdbXmlPublisher();
        $this->actorDocumentRepository = $actorDocumentRepository;
    }

    /**
     * @param CdbXmlPublisherInterface $cdbXmlPublisher
     * @return OfferToEventCdbXmlProjector
     */
    public function withCdbXmlPublisher(CdbXmlPublisherInterface $cdbXmlPublisher)
    {
        $c = clone $this;
        $c->cdbXmlPublisher = $cdbXmlPublisher;
        return $c;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $metadata = $domainMessage->getMetadata();

        $handlers = [
            EventTitleTranslated::class => 'applyTitleTranslated',
            PlaceTitleTranslated::class => 'applyTitleTranslated',
            EventCreated::class => 'applyEventCreated',
            EventDeleted::class => 'applyEventDeleted',
            PlaceCreated::class => 'applyPlaceCreated',
            PlaceDeleted::class => 'applyPlaceDeleted',
            EventDescriptionTranslated::class => 'applyDescriptionTranslated',
            PlaceDescriptionTranslated::class => 'applyDescriptionTranslated',
            EventLabelAdded::class => 'applyLabelAdded',
            PlaceLabelAdded::class => 'applyLabelAdded',
            EventLabelDeleted::class => 'applyLabelDeleted',
            PlaceLabelDeleted::class => 'applyLabelDeleted',
            EventImageAdded::class => 'applyImageAdded',
            PlaceImageAdded::class => 'applyImageAdded',
            EventImageUpdated::class => 'applyImageUpdated',
            PlaceImageUpdated::class => 'applyImageUpdated',
            EventImageRemoved::class => 'applyImageRemoved',
            PlaceImageRemoved::class => 'applyImageRemoved',
            EventMainImageSelected::class => 'applyMainImageSelected',
            PlaceMainImageSelected::class => 'applyMainImageSelected',
            EventBookingInfoUpdated::class => 'applyBookingInfoUpdated',
            PlaceBookingInfoUpdated::class => 'applyBookingInfoUpdated',
            EventContactPointUpdated::class => 'applyContactPointUpdated',
            PlaceContactPointUpdated::class => 'applyContactPointUpdated',
            EventOrganizerUpdated::class => 'applyOrganizerUpdated',
            EventOrganizerDeleted::class => 'applyOrganizerDeleted',
            PlaceOrganizerUpdated::class => 'applyOrganizerUpdated',
            PlaceOrganizerDeleted::class => 'applyOrganizerDeleted',
            EventTypicalAgeRangeUpdated::class => 'applyTypicalAgeRangeUpdated',
            EventTypicalAgeRangeDeleted::class => 'applyTypicalAgeRangeDeleted',
            PlaceTypicalAgeRangeUpdated::class => 'applyTypicalAgeRangeUpdated',
            PlaceTypicalAgeRangeDeleted::class => 'applyTypicalAgeRangeDeleted',
            EventMajorInfoUpdated::class => 'applyEventMajorInfoUpdated',
        ];

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];
            $cdbXmlDocument = $this->{$handler}($payload, $metadata);

            $this->documentRepository->save($cdbXmlDocument);

            $this->cdbXmlPublisher->publish($cdbXmlDocument, $domainMessage);
        }
    }

    /**
     * @param EventMajorInfoUpdated $eventMajorInfoUpdated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyEventMajorInfoUpdated(
        EventMajorInfoUpdated $eventMajorInfoUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get(
            $eventMajorInfoUpdated->getItemId()
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // set title
        foreach ($event->getDetails() as $detail) {
            if ($detail->getLanguage() == 'nl') {
                $detail->setTitle($eventMajorInfoUpdated->getTitle());
                break;
            }
        }

        // Set location and calendar info.
        $this->setLocation($eventMajorInfoUpdated->getLocation(), $event);
        $this->setCalendar($eventMajorInfoUpdated->getCalendar(), $event);

        // Set event type and theme.
        // @todo theme doesn't exist
        foreach ($event->getCategories() as $category) {
            if ($category->getType() == 'eventtype') {
                $category->setId($eventMajorInfoUpdated->getEventType()->getId());
                $category->setName($eventMajorInfoUpdated->getEventType()->getLabel());
            }

            if ($category->getType() == 'theme') {
                $category->setId($eventMajorInfoUpdated->getTheme()->getId());
                $category->setName($eventMajorInfoUpdated->getTheme()->getLabel());
            }
        }

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param EventCreated $eventCreated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyEventCreated(
        EventCreated $eventCreated,
        Metadata $metadata
    ) {
        $event = new CultureFeed_Cdb_Item_Event();
        $event->setCdbId($eventCreated->getEventId());

        $nlDetail = new CultureFeed_Cdb_Data_EventDetail();
        $nlDetail->setLanguage('nl');
        $nlDetail->setTitle($eventCreated->getTitle());

        $details = new CultureFeed_Cdb_Data_EventDetailList();
        $details->add($nlDetail);
        $event->setDetails($details);

        // Set location and calendar info.
        $this->setLocation($eventCreated->getLocation(), $event);
        $this->setCalendar($eventCreated->getCalendar(), $event);

        // Set event type and theme.
        $event->setCategories(new CultureFeed_Cdb_Data_CategoryList());
        $eventType = new CultureFeed_Cdb_Data_Category(
            'eventtype',
            $eventCreated->getEventType()->getId(),
            $eventCreated->getEventType()->getLabel()
        );
        $event->getCategories()->add($eventType);

        if ($eventCreated->getTheme() !== null) {
            $theme = new CultureFeed_Cdb_Data_Category(
                'theme',
                $eventCreated->getTheme()->getId(),
                $eventCreated->getTheme()->getLabel()
            );
            $event->getCategories()->add($theme);
        }

        // Empty contact info.
        $contactInfo = new CultureFeed_Cdb_Data_ContactInfo();
        $event->setContactInfo($contactInfo);

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
          ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
          ->fromCulturefeedCdbItem($event);
    }
    
    /**
     * @param EventDeleted $eventDeleted
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyEventDeleted(
        EventDeleted $eventDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($eventDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $event->setWfStatus('deleted');

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param PlaceCreated $placeCreated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyPlaceCreated(
        PlaceCreated $placeCreated,
        Metadata $metadata
    ) {
        $event = new CultureFeed_Cdb_Item_Event();
        $event->setCdbId($placeCreated->getPlaceId());
        $event->addKeyword('UDB3 place');

        $nlDetail = new CultureFeed_Cdb_Data_EventDetail();
        $nlDetail->setLanguage('nl');
        $nlDetail->setTitle($placeCreated->getTitle());

        $details = new CultureFeed_Cdb_Data_EventDetailList();
        $details->add($nlDetail);
        $event->setDetails($details);

        // Set location and calendar info.
        $this->setLocationForPlaceCreated($placeCreated, $event);
        $this->setCalendar($placeCreated->getCalendar(), $event);

        // Set event type and theme.
        $event->setCategories(new CultureFeed_Cdb_Data_CategoryList());
        $eventType = new CultureFeed_Cdb_Data_Category(
            'eventtype',
            $placeCreated->getEventType()->getId(),
            $placeCreated->getEventType()->getLabel()
        );
        $event->getCategories()->add($eventType);

        if ($placeCreated->getTheme() !== null) {
            $theme = new CultureFeed_Cdb_Data_Category(
                'theme',
                $placeCreated->getTheme()->getId(),
                $placeCreated->getTheme()->getLabel()
            );
            $event->getCategories()->add($theme);
        }

        // Empty contact info.
        $contactInfo = new CultureFeed_Cdb_Data_ContactInfo();
        $event->setContactInfo($contactInfo);

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param PlaceDeleted $placeDeleted
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyPlaceDeleted(
        PlaceDeleted $placeDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($placeDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $event->setWfStatus('deleted');

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractTitleTranslated $titleTranslated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     * @throws \CultureFeed_Cdb_ParseException
     */
    public function applyTitleTranslated(
        AbstractTitleTranslated $titleTranslated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($titleTranslated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $details = $event->getDetails();

        $detail = new CultureFeed_Cdb_Data_EventDetail();
        $detail->setLanguage($titleTranslated->getLanguage()->getCode());
        $detail->setTitle($titleTranslated->getTitle());

        $details->add($detail);
        $event->setDetails($details);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractDescriptionTranslated $descriptionTranslated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyDescriptionTranslated(
        AbstractDescriptionTranslated $descriptionTranslated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($descriptionTranslated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $details = $event->getDetails();

        $detail = new CultureFeed_Cdb_Data_EventDetail();
        $detail->setLanguage($descriptionTranslated->getLanguage()->getCode());
        $description = $descriptionTranslated->getDescription()->toNative();
        $detail->setLongDescription($description);
        $detail->setShortDescription(iconv_substr($description, 0, 400));

        $details->add($detail);
        $event->setDetails($details);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractBookingInfoUpdated $bookingInfoUpdated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyBookingInfoUpdated(
        AbstractBookingInfoUpdated $bookingInfoUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($bookingInfoUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $bookingInfo = $bookingInfoUpdated->getBookingInfo();

        $this->updateCdbItemByBookingInfo($event, $bookingInfo);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractContactPointUpdated $contactPointUpdated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyContactPointUpdated(
        AbstractContactPointUpdated $contactPointUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($contactPointUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $contactPoint = $contactPointUpdated->getContactPoint();
        $this->updateCdbItemByContactPoint($event, $contactPoint);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractLabelAdded $labelAdded
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyLabelAdded(
        AbstractLabelAdded $labelAdded,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($labelAdded->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $keywords = $event->getKeywords();
        $label = $labelAdded->getLabel()->__toString();
        $keyword = new CultureFeed_Cdb_Data_Keyword(
            $label,
            $labelAdded->getLabel()->isVisible()
        );

        if (!in_array($label, $keywords)) {
            $event->addKeyword($keyword);

            // Change the lastupdated attribute.
            $event = $this->metadataCdbItemEnricher
                ->enrich($event, $metadata);
        }

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractLabelDeleted $labelDeleted
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     * @throws \Exception
     */
    public function applyLabelDeleted(
        AbstractLabelDeleted $labelDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($labelDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $keywords = $event->getKeywords();
        $keyword = $labelDeleted->getLabel()->__toString();

        if (in_array($keyword, $keywords)) {
            $event->deleteKeyword($keyword);

            // Change the lastupdated attribute.
            $event = $this->metadataCdbItemEnricher
                ->enrich($event, $metadata);
        }

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractOrganizerUpdated $organizerUpdated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyOrganizerUpdated(
        AbstractOrganizerUpdated $organizerUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($organizerUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // load organizer from documentRepo & add to document
        $organizerCdbXml = $this->actorDocumentRepository->get($organizerUpdated->getOrganizerId());

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $organizerCdbXml->getCdbXml()
        );

        $organizer = new \CultureFeed_Cdb_Data_Organiser();
        $organizer->setCdbid($organizerUpdated->getOrganizerId());
        $organizer->setLabel($actor->getDetails()->getDetailByLanguage('nl')->getTitle());
        $organizer->setActor($actor);

        $event->setOrganiser($organizer);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractOrganizerDeleted $organizerDeleted
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyOrganizerDeleted(
        AbstractOrganizerDeleted $organizerDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($organizerDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $event->deleteOrganiser();

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractTypicalAgeRangeUpdated $ageRangeUpdated
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyTypicalAgeRangeUpdated(
        AbstractTypicalAgeRangeUpdated $ageRangeUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($ageRangeUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $ageArray = explode('-', $ageRangeUpdated->getTypicalAgeRange());
        $ageFrom = array_shift($ageArray);
        $event->setAgeFrom((int) $ageFrom);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractTypicalAgeRangeDeleted $ageRangeDeleted
     * @param Metadata $metadata
     * @return Repository\CdbXmlDocument
     */
    public function applyTypicalAgeRangeDeleted(
        AbstractTypicalAgeRangeDeleted $ageRangeDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->documentRepository->get($ageRangeDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $event->setAgeFrom(0);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * Set the location on the cdb event based on an eventCreated event.
     *
     * @param Location $eventLocation
     * @param CultureFeed_Cdb_Item_Event $cdbEvent
     * @throws \CultuurNet\UDB3\EntityNotFoundException
     */
    private function setLocation(Location $eventLocation, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $placeCdbXml = $this->documentRepository->get($eventLocation->getCdbid());

        $place = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXml->getCdbXml()
        );

        $location = $place->getLocation();
        
        $location->setLabel($eventLocation->getName());
        $cdbEvent->setLocation($location);
    }

    /**
     * Set the Calendar on the cdb event.
     *
     * @param CalendarInterface $eventCalendar
     * @param CultureFeed_Cdb_Item_Event $cdbEvent
     */
    public function setCalendar(CalendarInterface $eventCalendar, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        // Store opening hours.
        $openingHours = $eventCalendar->getOpeningHours();
        $weekScheme = null;

        if (!empty($openingHours)) {
            // CDB2 requires an entry for every day.
            $requiredDays = array(
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            );
            $weekscheme = new CultureFeed_Cdb_Data_Calendar_Weekscheme();

            // Multiple opening times can happen on same day. Store them in array.
            $openingTimesPerDay = array(
                'monday' => array(),
                'tuesday' => array(),
                'wednesday' => array(),
                'thursday' => array(),
                'friday' => array(),
                'saturday' => array(),
                'sunday' => array(),
            );

            foreach ($openingHours as $openingHour) {
                // In CDB2 every day needs to be a seperate entry.
                foreach ($openingHour->dayOfWeek as $day) {
                    $openingTimesPerDay[$day][] = new CultureFeed_Cdb_Data_Calendar_OpeningTime(
                        $openingHour->opens . ':00',
                        $openingHour->closes . ':00'
                    );
                }

            }

            // Create the opening times correctly
            foreach ($openingTimesPerDay as $day => $openingTimes) {
                // Empty == closed.
                if (empty($openingTimes)) {
                    $openingInfo = new CultureFeed_Cdb_Data_Calendar_SchemeDay(
                        $day,
                        CultureFeed_Cdb_Data_Calendar_SchemeDay::SCHEMEDAY_OPEN_TYPE_CLOSED
                    );
                } else {
                    // Add all opening times.
                    $openingInfo = new CultureFeed_Cdb_Data_Calendar_SchemeDay(
                        $day,
                        CultureFeed_Cdb_Data_Calendar_SchemeDay::SCHEMEDAY_OPEN_TYPE_OPEN
                    );
                    foreach ($openingTimes as $openingTime) {
                        $openingInfo->addOpeningTime($openingTime);
                    }
                }

                $weekscheme->setDay($day, $openingInfo);
            }

        }

        // Multiple days.
        if ($eventCalendar->getType() == Calendar::MULTIPLE) {
            $calendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
            foreach ($eventCalendar->getTimestamps() as $timestamp) {
                $startdate = strtotime($timestamp->getStartDate());
                $enddate = strtotime($timestamp->getEndDate());
                $startHour = date('H:i:s', $startdate);
                if ($startHour == '00:00:00') {
                    $startHour = null;
                }
                $endHour = date('H:i:s', $enddate);
                if ($endHour == '00:00:00') {
                    $endHour = null;
                }
                $calendar->add(
                    new CultureFeed_Cdb_Data_Calendar_Timestamp(
                        date('Y-m-d', $startdate),
                        $startHour,
                        $endHour
                    )
                );
            }
            // Single day.
        } elseif ($eventCalendar->getType() == Calendar::SINGLE) {
            $calendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
            $startdate = strtotime($eventCalendar->getStartDate());
            $enddate = strtotime($eventCalendar->getEndDate());
            $startHour = date('H:i:s', $startdate);
            if ($startHour == '00:00:00') {
                $startHour = null;
            }
            $endHour = date('H:i:s', $enddate);
            if ($endHour == '00:00:00') {
                $endHour = null;
            }
            $calendar->add(
                new CultureFeed_Cdb_Data_Calendar_Timestamp(
                    date('Y-m-d', $startdate),
                    $startHour,
                    $endHour
                )
            );
            // Period.
        } elseif ($eventCalendar->getType() == Calendar::PERIODIC) {
            $calendar = new CultureFeed_Cdb_Data_Calendar_PeriodList();
            $startdate = date('Y-m-d', strtotime($eventCalendar->getStartDate()));
            $enddate = date('Y-m-d', strtotime($eventCalendar->getEndDate()));

            $period = new CultureFeed_Cdb_Data_Calendar_Period($startdate, $enddate);
            if (!empty($weekScheme)) {
                $calendar->setWeekScheme($weekscheme);
            }
            $calendar->add($period);

            // Permanent
        } elseif ($eventCalendar->getType() == Calendar::PERMANENT) {
            $calendar = new CultureFeed_Cdb_Data_Calendar_Permanent();
            if (!empty($weekScheme)) {
                $calendar->setWeekScheme($weekscheme);
            }

        }

        $cdbEvent->setCalendar($calendar);
    }

    /**
     * Set the location on the cdbEvent based on a PlaceCreated event.
     * @param PlaceCreated $placeCreated
     * @param CultureFeed_Cdb_Item_Event $cdbEvent
     */
    private function setLocationForPlaceCreated(PlaceCreated $placeCreated, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $address = $placeCreated->getAddress();
        $cdbAddress = new CultureFeed_Cdb_Data_Address(
            $this->getPhysicalAddressForUdb3Address($address)
        );

        $location = new CultureFeed_Cdb_Data_Location($cdbAddress);
        $location->setLabel($placeCreated->getTitle());
        $cdbEvent->setLocation($location);
    }

    /**
     * Create a physical addres based on a given udb3 address.
     * @param Address $address
     */
    protected function getPhysicalAddressForUdb3Address(Address $address)
    {
        $physicalAddress = new CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setCountry($address->getCountry());
        $physicalAddress->setCity($address->getLocality());
        $physicalAddress->setZip($address->getPostalCode());

        // @todo This is not an exact mapping, because we do not have a separate
        // house number in JSONLD, this should be fixed somehow. Probably it's
        // better to use another read model than JSON-LD for this purpose.
        $streetParts = explode(' ', $address->getStreetAddress());

        if (count($streetParts) > 1) {
            $number = array_pop($streetParts);
            $physicalAddress->setStreet(implode(' ', $streetParts));
            $physicalAddress->setHouseNumber($number);
        } else {
            $physicalAddress->setStreet($address->getStreetAddress());
        }

        return $physicalAddress;
    }

    /**
     * Update the cdb item based on a bookingInfo object.
     *
     * @param CultureFeed_Cdb_Item_Event $cdbItem
     * @param BookingInfo $bookingInfo
     */
    protected function updateCdbItemByBookingInfo(
        CultureFeed_Cdb_Item_Event $cdbItem,
        BookingInfo $bookingInfo
    ) {

        // Add the booking Period.
        $bookingPeriod = $cdbItem->getBookingPeriod();
        if (empty($bookingPeriod)) {
            $bookingPeriod = new CultureFeed_Cdb_Data_Calendar_BookingPeriod(
                null,
                null
            );
        }

        if ($bookingInfo->getAvailabilityStarts()) {
            $startDate = new DateTime($bookingInfo->getAvailabilityStarts());
            $bookingPeriod->setDateFrom($startDate->getTimestamp());
        }
        if ($bookingInfo->getAvailabilityEnds()) {
            $endDate = new DateTime($bookingInfo->getAvailabilityEnds());
            $bookingPeriod->setDateTill($endDate->getTimestamp());
        }
        $cdbItem->setBookingPeriod($bookingPeriod);

        // Add the contact info.
        $contactInfo = $cdbItem->getContactInfo();
        if (!$contactInfo instanceof CultureFeed_Cdb_Data_ContactInfo) {
            $contactInfo = new CultureFeed_Cdb_Data_ContactInfo();
        }

        $newContactInfo = $this->copyContactInfoWithoutReservationChannels(
            $contactInfo
        );

        if (!empty($bookingInfo->getPhone())) {
            $newContactInfo->addPhone(
                new CultureFeed_Cdb_Data_Phone(
                    $bookingInfo->getPhone(),
                    null,
                    null,
                    true
                )
            );
        }

        if (!empty($bookingInfo->getUrl())) {
            $newContactInfo->addUrl(
                new CultureFeed_Cdb_Data_Url(
                    $bookingInfo->getUrl(),
                    false,
                    true
                )
            );
        }

        if (!empty($bookingInfo->getEmail())) {
            $newContactInfo->addMail(
                new CultureFeed_Cdb_Data_Mail(
                    $bookingInfo->getEmail(),
                    false,
                    true
                )
            );
        }

        $cdbItem->setContactInfo($newContactInfo);
    }

    /**
     * @param CultureFeed_Cdb_Data_ContactInfo $contactInfo
     * @return CultureFeed_Cdb_Data_ContactInfo
     */
    private function copyContactInfoWithoutReservationChannels(
        CultureFeed_Cdb_Data_ContactInfo $contactInfo
    ) {
        $newContactInfo = new CultureFeed_Cdb_Data_ContactInfo();

        foreach ($contactInfo->getAddresses() as $address) {
            $newContactInfo->addAddress($address);
        }

        /** @var CultureFeed_Cdb_Data_Phone $phone */
        foreach ($contactInfo->getPhones() as $phone) {
            if (!$phone->isForReservations()) {
                $newContactInfo->addPhone($phone);
            }
        }

        /** @var CultureFeed_Cdb_Data_Url $url */
        foreach ($contactInfo->getUrls() as $url) {
            if (!$url->isForReservations()) {
                $newContactInfo->addUrl($url);
            }
        }

        foreach ($contactInfo->getMails() as $mail) {
            if (!$mail->isForReservations()) {
                $newContactInfo->addMail($mail);
            }
        }

        return $newContactInfo;
    }

    /**
     * Update a cdb item based on a contact point.
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param \CultuurNet\UDB3\UDB2\ContactPoint $contactPoint
     */
    protected function updateCdbItemByContactPoint(
        CultureFeed_Cdb_Item_Base $cdbItem,
        ContactPoint $contactPoint
    ) {

        $contactInfo = $cdbItem->getContactInfo();

        // Remove non-reservation phones and add new ones.
        foreach ($contactInfo->getPhones() as $phoneIndex => $phone) {
            if (!$phone->isForReservations()) {
                $contactInfo->removePhone($phoneIndex);
            }
        }
        $phones = $contactPoint->getPhones();
        foreach ($phones as $phone) {
            $contactInfo->addPhone(new CultureFeed_Cdb_Data_Phone($phone));
        }

        // Remove non-reservation urls and add new ones.
        foreach ($contactInfo->getUrls() as $urlIndex => $url) {
            if (!$url->isForReservations()) {
                $contactInfo->removeUrl($urlIndex);
            }
        }
        $urls = $contactPoint->getUrls();
        foreach ($urls as $url) {
            $contactInfo->addUrl(new CultureFeed_Cdb_Data_Url($url));
        }

        // Remove non-reservation emails and add new ones.
        foreach ($contactInfo->getMails() as $mailIndex => $mail) {
            if (!$mail->isForReservations()) {
                $contactInfo->removeMail($mailIndex);
            }
        }
        $emails = $contactPoint->getEmails();
        foreach ($emails as $email) {
            $contactInfo->addMail(new CultureFeed_Cdb_Data_Mail($email));
        }
        $cdbItem->setContactInfo($contactInfo);

    }
}
