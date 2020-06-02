<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\Formatter\NumberFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use CultureFeed_Cdb_Data_ActorDetail;
use CultureFeed_Cdb_Data_Calendar_BookingPeriod;
use CultureFeed_Cdb_Data_Calendar_Permanent;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Data_CategoryList;
use CultureFeed_Cdb_Data_ContactInfo;
use CultureFeed_Cdb_Data_EventDetail;
use CultureFeed_Cdb_Data_EventDetailList;
use CultureFeed_Cdb_Data_File;
use CultureFeed_Cdb_Data_Keyword;
use CultureFeed_Cdb_Data_Location;
use CultureFeed_Cdb_Data_Mail;
use CultureFeed_Cdb_Data_Media;
use CultureFeed_Cdb_Data_Phone;
use CultureFeed_Cdb_Data_Url;
use CultureFeed_Cdb_Item_Actor;
use CultureFeed_Cdb_Item_Base;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar\CalendarConverter;
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\Category;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractorInterface;
use CultuurNet\UDB3\Cdb\Description\MergedDescription;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategoryListFilter;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\AnyOff;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\ID;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\Not;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\Type;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\CulturefeedSlugger;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated as EventBookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated as EventCalendarUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated as EventContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated as EventDescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated as EventDescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated as EventFacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved as EventImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated as EventImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved as EventLabelRemoved;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MainImageSelected as EventMainImageSelected;
use CultuurNet\UDB3\Event\Events\Moderation\Published as EventPublished;
use CultuurNet\UDB3\Event\Events\Moderation\Approved as EventApproved;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected as EventRejected;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate as EventFlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate as EventFlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated as EventPriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\ThemeUpdated as EventThemeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Events\AbstractFacilitiesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractThemeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypeUpdated;
use CultuurNet\UDB3\Place\Events\ThemeUpdated as PlaceThemeUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated as EventTitleTranslated;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted as EventOrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated as EventOrganizerUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated as EventTypeUpdated;
use CultuurNet\UDB3\Place\Events\TypeUpdated as PlaceTypeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated as EventTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted as EventTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\AvailableTo;
use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractPriceInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractMainImageSelected;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractApproved;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractPublished;
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\Place\Events\TitleUpdated as PlaceTitleUpdated;
use CultuurNet\UDB3\Event\Events\TitleUpdated as EventTitleUpdated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated as PlaceBookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated as PlaceCalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated as PlaceContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated as PlaceDescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated as PlaceDescriptionUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated as PlaceFacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\ImageAdded as PlaceImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved as PlaceImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated as PlaceImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved as PlaceLabelRemoved;
use CultuurNet\UDB3\Place\Events\MainImageSelected as PlaceMainImageSelected;
use CultuurNet\UDB3\Place\Events\Moderation\Published as PlacePublished;
use CultuurNet\UDB3\Place\Events\Moderation\Approved as PlaceApproved;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected as PlaceRejected;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate as PlaceFlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate as PlaceFlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleTranslated as PlaceTitleTranslated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\SluggerInterface;
use CultuurNet\UDB3\StringFilter\StringFilterInterface;
use CultuurNet\UDB3\Theme;
use DateTimeInterface;
use League\Uri\Modifiers\AbstractUriModifier;
use League\Uri\Modifiers\Normalize;
use League\Uri\Schemes\Http;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;
use ValueObjects\Identity\UUID;
use Rhumsaa\Uuid\Uuid as BaseUuid;

/**
 * Class OfferToCdbXmlProjector
 * This projector takes UDB3 domain messages, projects them to CdbXml and then
 * publishes the changes to a public URL.
 */
class OfferToCdbXmlProjector implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const SCHOOLS_CATEGORY_ID = '2.1.3.0.0';

    /**
     * @var array
     */
    protected $imageTypes = [
        CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO,
        CultureFeed_Cdb_Data_File::MEDIA_TYPE_IMAGEWEB,
    ];

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
     * @var DocumentRepositoryInterface
     */
    private $actorDocumentRepository;

    /**
     * @var DateFormatterInterface
     */
    private $dateFormatter;

    /**
     * @var AddressFactoryInterface
     */
    private $addressFactory;

    /**
     * @var \CultuurNet\UDB3\StringFilter\StringFilterInterface
     */
    private $longDescriptionFilter;

    /**
     * @var \CultuurNet\UDB3\StringFilter\StringFilterInterface
     */
    private $shortDescriptionFilter;

    /**
     * @var CurrencyRepositoryInterface
     */
    private $currencyRepository;

    /**
     * @var NumberFormatRepositoryInterface
     */
    private $numberFormatRepository;

    /**
     * @var EventCdbIdExtractorInterface
     */
    private $eventCdbIdExtractor;

    /**
     * @var string[]
     */
    private $basePriceTranslations;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    /**
     * @var AbstractUriModifier
     */
    protected $uriNormalizer;

    /**
     * @var CalendarConverter
     */
    protected $calendarConverter;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param MetadataCdbItemEnricherInterface $metadataCdbItemEnricher
     * @param DocumentRepositoryInterface $actorDocumentRepository
     * @param DateFormatterInterface $dateFormatter
     * @param AddressFactoryInterface $addressFactory
     * @param StringFilterInterface $longDescriptionFilter
     * @param StringFilterInterface $shortDescriptionFilter
     * @param CurrencyRepositoryInterface $currencyRepository
     * @param NumberFormatRepositoryInterface $numberFormatRepository
     * @param EventCdbIdExtractorInterface $eventCdbIdExtractor
     * @param string[] $basePriceTranslations
     *   Associative array of language codes and a "base tariff" label for each language.
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        MetadataCdbItemEnricherInterface $metadataCdbItemEnricher,
        DocumentRepositoryInterface $actorDocumentRepository,
        DateFormatterInterface $dateFormatter,
        AddressFactoryInterface $addressFactory,
        StringFilterInterface $longDescriptionFilter,
        StringFilterInterface $shortDescriptionFilter,
        CurrencyRepositoryInterface $currencyRepository,
        NumberFormatRepositoryInterface $numberFormatRepository,
        EventCdbIdExtractorInterface $eventCdbIdExtractor,
        array $basePriceTranslations = []
    ) {
        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->metadataCdbItemEnricher = $metadataCdbItemEnricher;
        $this->actorDocumentRepository = $actorDocumentRepository;
        $this->dateFormatter = $dateFormatter;
        $this->addressFactory = $addressFactory;
        $this->longDescriptionFilter = $longDescriptionFilter;
        $this->shortDescriptionFilter = $shortDescriptionFilter;
        $this->currencyRepository = $currencyRepository;
        $this->numberFormatRepository = $numberFormatRepository;
        $this->eventCdbIdExtractor = $eventCdbIdExtractor;
        $this->basePriceTranslations = $basePriceTranslations;
        $this->slugger = new CulturefeedSlugger();
        $this->logger = new NullLogger();
        $this->uriNormalizer = new Normalize();
        $this->calendarConverter = new CalendarConverter();
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
            AddressUpdated::class => 'applyAddressUpdated',
            PlaceFacilitiesUpdated::class => 'applyFacilitiesUpdated',
            EventFacilitiesUpdated::class => 'applyFacilitiesUpdated',
            EventTitleTranslated::class => 'applyTitleTranslated',
            PlaceTitleTranslated::class => 'applyTitleTranslated',
            EventTitleUpdated::class => 'applyTitleUpdated',
            PlaceTitleUpdated::class => 'applyTitleUpdated',
            EventCreated::class => 'applyEventCreated',
            EventCopied::class => 'applyEventCopied',
            EventDeleted::class => 'applyEventDeleted',
            PlaceCreated::class => 'applyPlaceCreated',
            PlaceDeleted::class => 'applyPlaceDeleted',
            EventDescriptionTranslated::class => 'applyDescriptionTranslated',
            PlaceDescriptionTranslated::class => 'applyDescriptionTranslated',
            EventLabelAdded::class => 'applyLabelAdded',
            PlaceLabelAdded::class => 'applyLabelAdded',
            EventLabelRemoved::class => 'applyLabelRemoved',
            PlaceLabelRemoved::class => 'applyLabelRemoved',
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
            EventPriceInfoUpdated::class => 'applyPriceInfoUpdated',
            EventContactPointUpdated::class => 'applyContactPointUpdated',
            PlaceContactPointUpdated::class => 'applyContactPointUpdated',
            EventOrganizerUpdated::class => 'applyOrganizerUpdated',
            EventOrganizerDeleted::class => 'applyOrganizerDeleted',
            EventTypicalAgeRangeUpdated::class => 'applyTypicalAgeRangeUpdated',
            EventTypicalAgeRangeDeleted::class => 'applyTypicalAgeRangeDeleted',
            EventDescriptionUpdated::class => 'applyDescriptionUpdated',
            PlaceDescriptionUpdated::class => 'applyDescriptionUpdated',
            EventMajorInfoUpdated::class => 'applyEventMajorInfoUpdated',
            PlaceMajorInfoUpdated::class => 'applyPlaceMajorInfoUpdated',
            EventCalendarUpdated::class => 'applyEventCalendarUpdated',
            PlaceCalendarUpdated::class => 'applyPlaceCalendarUpdated',
            LocationUpdated::class => 'applyLocationUpdated',
            EventImportedFromUDB2::class => 'applyEventImportedFromUdb2',
            EventUpdatedFromUDB2::class => 'applyEventUpdatedFromUdb2',
            PlaceImportedFromUDB2::class => 'applyPlaceImportedFromUdb2',
            PlaceUpdatedFromUDB2::class => 'applyPlaceUpdatedFromUdb2',
            EventPublished::class => 'applyPublished',
            PlacePublished::class => 'applyPublished',
            EventApproved::class => 'applyApproved',
            PlaceApproved::class => 'applyApproved',
            EventRejected::class => 'applyRejected',
            PlaceRejected::class => 'applyRejected',
            EventFlaggedAsDuplicate::class => 'applyRejected',
            PlaceFlaggedAsDuplicate::class => 'applyRejected',
            EventFlaggedAsInappropriate::class => 'applyRejected',
            PlaceFlaggedAsInappropriate::class => 'applyRejected',
            AudienceUpdated::class => 'applyAudienceUpdated',
            EventTypeUpdated::class => 'applyTypeUpdated',
            PlaceTypeUpdated::class => 'applyTypeUpdated',
            EventThemeUpdated::class => 'applyThemeUpdated',
            PlaceThemeUpdated::class => 'applyThemeUpdated',
        ];

        $this->logger->info('found message ' . $payloadClassName . ' in OfferToCdbXmlProjector');

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];

            try {
                $this->logger->info(
                    'handling message ' . $payloadClassName . ' using ' .
                    $handlers[$payloadClassName] . ' in OfferToCdbXmlProjector'
                );

                $cdbXmlDocument = $this->{$handler}($payload, $metadata);

                if ($cdbXmlDocument) {
                    $this->documentRepository->save($cdbXmlDocument);
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Handle error for uuid=' . $domainMessage->getId()
                    . ' for type ' . $domainMessage->getType()
                    . ' recorded on ' .$domainMessage->getRecordedOn()->toString(),
                    [
                        'exception' => get_class($exception),
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        } else {
            $this->logger->info('no handler found for message ' . $payloadClassName);
        }
    }

    public function applyTypeUpdated(
        AbstractTypeUpdated $typeUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($typeUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $this->replaceCategoryByDomain($offer, $typeUpdated->getType());

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    public function applyThemeUpdated(
        AbstractThemeUpdated $themeUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($themeUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $this->replaceCategoryByDomain($offer, $themeUpdated->getTheme());

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param PlaceImportedFromUDB2 $placeImportedFromUDB2
     * @param \Broadway\Domain\Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyPlaceImportedFromUdb2(
        PlaceImportedFromUDB2 $placeImportedFromUDB2,
        Metadata $metadata
    ) {
        return $this->updatePlaceFromCdbXml(
            $placeImportedFromUDB2->getCdbXml(),
            $placeImportedFromUDB2->getCdbXmlNamespaceUri(),
            $metadata
        );
    }

    /**
     * @param PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2
     * @param \Broadway\Domain\Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyPlaceUpdatedFromUdb2(
        PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2,
        Metadata $metadata
    ) {
        return $this->updatePlaceFromCdbXml(
            $placeUpdatedFromUDB2->getCdbXml(),
            $placeUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $metadata
        );
    }

    /**
     * @param EventImportedFromUDB2 $eventImportedFromCdbXml
     * @param \Broadway\Domain\Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyEventImportedFromUdb2(
        EventImportedFromUDB2 $eventImportedFromCdbXml,
        Metadata $metadata
    ) {
        return $this->updateEventFromCdbXml(
            $eventImportedFromCdbXml->getCdbXml(),
            $eventImportedFromCdbXml->getCdbXmlNamespaceUri(),
            $metadata
        );
    }

    /**
     * @param EventUpdatedFromUDB2 $eventUpdatedFromUdb2
     * @param \Broadway\Domain\Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyEventUpdatedFromUdb2(
        EventUpdatedFromUDB2 $eventUpdatedFromUdb2,
        Metadata $metadata
    ) {
        return $this->updateEventFromCdbXml(
            $eventUpdatedFromUdb2->getCdbXml(),
            $eventUpdatedFromUdb2->getCdbXmlNamespaceUri(),
            $metadata
        );
    }

    /**
     * @param PlaceMajorInfoUpdated $placeMajorInfoUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyPlaceMajorInfoUpdated(
        PlaceMajorInfoUpdated $placeMajorInfoUpdated,
        Metadata $metadata
    ) {
        $actorCdbXml = $this->getCdbXmlDocument(
            $placeMajorInfoUpdated->getPlaceId()
        );

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $actorCdbXml->getCdbXml()
        );

        // Update the title for the main language.
        $details = $actor->getDetails();
        $detail = $details->getFirst();
        if ($detail) {
            $detail->setTitle($placeMajorInfoUpdated->getTitle());
        }

        // Contact info.
        $address = $placeMajorInfoUpdated->getAddress();
        $this->setCdbActorAddress($actor, $address);

        $cdbCalendar = $this->calendarConverter->toCdbCalendar(
            $placeMajorInfoUpdated->getCalendar()
        );
        if ($cdbCalendar instanceof CultureFeed_Cdb_Data_Calendar_Permanent) {
            $weekscheme = $cdbCalendar->getWeekScheme();
            empty($weekscheme) ?: $actor->setWeekScheme($weekscheme);
        }

        $this->setItemAvailableToFromCalendar(
            $placeMajorInfoUpdated->getCalendar(),
            $actor
        );

        // set eventtype and theme
        $this->updateCategories(
            $actor,
            $placeMajorInfoUpdated->getEventType(),
            $placeMajorInfoUpdated->getTheme()
        );

        // Add metadata like createdby, creationdate, etc to the actor.
        $actor = $this->metadataCdbItemEnricher
            ->enrich($actor, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param EventMajorInfoUpdated $eventMajorInfoUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyEventMajorInfoUpdated(
        EventMajorInfoUpdated $eventMajorInfoUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument(
            $eventMajorInfoUpdated->getItemId()
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // Update the title for the main language.
        $detail = $event->getDetails()->getFirst();
        if ($detail) {
            $detail->setTitle($eventMajorInfoUpdated->getTitle());
        }

        // Set location and calendar info.
        $this->setLocation(
            $eventMajorInfoUpdated->getLocation(),
            $event
        );
        $this->setCalendar($eventMajorInfoUpdated->getCalendar(), $event);

        $this->setItemAvailableToFromCalendar(
            $eventMajorInfoUpdated->getCalendar(),
            $event
        );

        $this->updateCategories(
            $event,
            $eventMajorInfoUpdated->getEventType(),
            $eventMajorInfoUpdated->getTheme()
        );

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param LocationUpdated $locationUpdated
     * @param Metadata $metadata
     *
     * @return CdbXmlDocument
     */
    public function applyLocationUpdated(
        LocationUpdated $locationUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument(
            $locationUpdated->getItemId()
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // Set the location.
        $this->setLocation($locationUpdated->getLocationId(), $event);

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param EventCalendarUpdated $calendarUpdated
     * @param Metadata $metadata
     *
     * @return CdbXmlDocument
     */
    public function applyEventCalendarUpdated(
        EventCalendarUpdated $calendarUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument(
            $calendarUpdated->getItemId()
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $this->setCalendar($calendarUpdated->getCalendar(), $event);

        $this->setItemAvailableToFromCalendar(
            $calendarUpdated->getCalendar(),
            $event
        );

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param PlaceCalendarUpdated $calendarUpdated
     * @param Metadata $metadata
     *
     * @return CdbXmlDocument
     */
    public function applyPlaceCalendarUpdated(
        PlaceCalendarUpdated $calendarUpdated,
        Metadata $metadata
    ) {
        $actorCdbXml = $this->getCdbXmlDocument(
            $calendarUpdated->getItemId()
        );

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $actorCdbXml->getCdbXml()
        );

        $cdbCalendar = $this->calendarConverter->toCdbCalendar(
            $calendarUpdated->getCalendar()
        );
        if ($cdbCalendar instanceof CultureFeed_Cdb_Data_Calendar_Permanent) {
            $weekscheme = $cdbCalendar->getWeekScheme();
            empty($weekscheme) ?: $actor->setWeekScheme($weekscheme);
        }

        $this->setItemAvailableToFromCalendar(
            $calendarUpdated->getCalendar(),
            $actor
        );

        // Add metadata like createdby, creationdate, etc to the actor.
        $actor = $this->metadataCdbItemEnricher
            ->enrich($actor, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param EventCreated $eventCreated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyEventCreated(
        EventCreated $eventCreated,
        Metadata $metadata
    ) {
        $event = new CultureFeed_Cdb_Item_Event();
        $event->setCdbId($eventCreated->getEventId());

        $mainLanguageDetail = new CultureFeed_Cdb_Data_EventDetail();
        $mainLanguageDetail->setLanguage($eventCreated->getMainLanguage()->getCode());
        $mainLanguageDetail->setTitle($eventCreated->getTitle());

        $details = new CultureFeed_Cdb_Data_EventDetailList();
        $details->add($mainLanguageDetail);
        $event->setDetails($details);

        // Empty contact info.
        $contactInfo = new CultureFeed_Cdb_Data_ContactInfo();
        $event->setContactInfo($contactInfo);

        // Set location and calendar info.
        $this->setLocation(
            $eventCreated->getLocation(),
            $event
        );
        $this->setCalendar($eventCreated->getCalendar(), $event);

        $this->setItemAvailableToFromCalendar(
            $eventCreated->getCalendar(),
            $event
        );

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

        // Set availablefrom if publication date is set.
        $this->setItemAvailableFrom($eventCreated, $event);

        $event->setWfStatus(WorkflowStatus::DRAFT()->toNative());

        $event->setPrivate(false);

        // Add metadata like createdby, creationdate, etc to the actor.
        $event = $this->metadataCdbItemEnricher
          ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
          ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param EventCopied $eventCopied
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyEventCopied(EventCopied $eventCopied, Metadata $metadata)
    {
        $eventCdbXml = $this->getCdbXmlDocument($eventCopied->getOriginalEventId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // Set the new cdbid.
        $event->setCdbId($eventCopied->getItemId());

        // Set the new calendar.
        $this->setCalendar($eventCopied->getCalendar(), $event);

        // Set availability
        $this->setItemAvailableToFromCalendar($eventCopied->getCalendar(), $event);
        $event->setAvailableFrom(null);

        // Set the workflow status.
        $event->setWfStatus(WorkflowStatus::DRAFT()->toNative());

        // Remove all labels.
        $keywords = $event->getKeywords(true);
        foreach ($keywords as $keyword) {
            $event->deleteKeyword($keyword);
        }

        // Update metadata like created-by, creation-date, last-updated and last-updated-by.
        // Make sure to first clear created-by and creation-date else they won't be updated.
        $event->setCreationDate(null);
        $event->setCreatedBy(null);
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param EventDeleted $eventDeleted
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyEventDeleted(
        EventDeleted $eventDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument($eventDeleted->getItemId());

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
     * @return CdbXmlDocument
     */
    public function applyPlaceCreated(
        PlaceCreated $placeCreated,
        Metadata $metadata
    ) {
        // Actor.
        $actor = new \CultureFeed_Cdb_Item_Actor();
        $actor->setCdbId($placeCreated->getPlaceId());
        $actor->setAsset(true);

        // Details.
        $mainLanguageDetail = new \CultureFeed_Cdb_Data_ActorDetail();
        $mainLanguageDetail->setLanguage($placeCreated->getMainLanguage()->getCode());
        $mainLanguageDetail->setTitle($placeCreated->getTitle());

        $details = new \CultureFeed_Cdb_Data_ActorDetailList();
        $details->add($mainLanguageDetail);
        $actor->setDetails($details);

        // Contact info.
        $contactInfo = new \CultureFeed_Cdb_Data_ContactInfo();

        $udb3Address = $placeCreated->getAddress();
        $address = $this->addressFactory->fromUdb3Address($udb3Address);
        $contactInfo->addAddress($address);

        $actor->setContactInfo($contactInfo);

        // Categories.
        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $categoryList->add(
            new \CultureFeed_Cdb_Data_Category(
                'actortype',
                '8.15.0.0.0',
                'Locatie'
            )
        );
        $categoryList->add(
            new CultureFeed_Cdb_Data_Category(
                'eventtype',
                $placeCreated->getEventType()->getId(),
                $placeCreated->getEventType()->getLabel()
            )
        );
        $actor->setCategories($categoryList);

        $this->setItemAvailableToFromCalendar(
            $placeCreated->getCalendar(),
            $actor
        );

        // Set availablefrom if publication date is set.
        $this->setItemAvailableFrom($placeCreated, $actor);

        $actor->setWfStatus(WorkflowStatus::DRAFT()->toNative());

        // Add metadata like createdby, creationdate, etc to the actor.
        $actor = $this->metadataCdbItemEnricher
          ->enrich($actor, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
          ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param PlaceDeleted $placeDeleted
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyPlaceDeleted(
        PlaceDeleted $placeDeleted,
        Metadata $metadata
    ) {
        $actorCdbXml = $this->getCdbXmlDocument(
            $placeDeleted->getItemId()
        );

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $actorCdbXml->getCdbXml()
        );

        $actor->setWfStatus('deleted');

        // Add metadata like createdby, creationdate, etc to the actor.
        $actor = $this->metadataCdbItemEnricher
            ->enrich($actor, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param AbstractTitleTranslated $titleTranslated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     * @throws \CultureFeed_Cdb_ParseException
     */
    public function applyTitleTranslated(
        AbstractTitleTranslated $titleTranslated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($titleTranslated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $languageCode = $titleTranslated->getLanguage()->getCode();
        $title = $titleTranslated->getTitle()->toNative();

        $details = $offer->getDetails();
        $detail = $details->getDetailByLanguage($languageCode);

        if (!empty($detail)) {
            $detail->setTitle($title);
        } else {
            $detail = $this->createOfferItemCdbDetail($offer);
            $detail->setLanguage($languageCode);
            $detail->setTitle($title);
            $details->add($detail);
        }

        $offer->setDetails($details);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * CDBXML does not keep track of the main language.
     * The first detail returned by the iterator is considered the oldest.
     * The oldest detail should be using the main language because it is created before you start translating.
     *
     * @param AbstractTitleUpdated $titleUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyTitleUpdated(
        AbstractTitleUpdated $titleUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($titleUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $details = $offer->getDetails();
        $details->rewind();
        /** @var \CultureFeed_Cdb_Data_Detail $mainLanguageDetail */
        $mainLanguageDetail = $details->current();

        $mainLanguageDetail->setTitle($titleUpdated->getTitle()->toNative());

        $offer->setDetails($details);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractDescriptionTranslated $descriptionTranslated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyDescriptionTranslated(
        AbstractDescriptionTranslated $descriptionTranslated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($descriptionTranslated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $languageCode = $descriptionTranslated->getLanguage()->getCode();
        $description = $descriptionTranslated->getDescription()->toNative();

        $details = $offer->getDetails();
        $detail = $details->getDetailByLanguage($languageCode);

        if (!empty($detail)) {
            $detail->setLongDescription(
                $this->longDescriptionFilter->filter($description)
            );
            $detail->setShortDescription(
                $this->shortDescriptionFilter->filter($description)
            );
        } else {
            $detail = $this->createOfferItemCdbDetail($offer);
            $detail->setLanguage($descriptionTranslated->getLanguage()->getCode());

            $mainLanguageDetail = $details->getFirst();

            $detail->setTitle($mainLanguageDetail->getTitle());
            $detail->setLongDescription(
                $this->longDescriptionFilter->filter($description)
            );
            $detail->setShortDescription(
                $this->shortDescriptionFilter->filter($description)
            );

            $details->add($detail);
        }

        $offer->setDetails($details);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractDescriptionUpdated $descriptionUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyDescriptionUpdated(
        AbstractDescriptionUpdated $descriptionUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($descriptionUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $details = $offer->getDetails();
        $mainLanguageDetail = $details->getFirst();

        if (empty($mainLanguageDetail)) {
            $mainLanguageDetail = $this->createOfferItemCdbDetail($offer);
            $mainLanguageDetail->setLanguage('nl');
            $details->add($mainLanguageDetail);
        }

        $description = $descriptionUpdated->getDescription()->toNative();

        $shortDescription = $this->shortDescriptionFilter->filter($description);
        $longDescription = $this->longDescriptionFilter->filter($description);

        if ($offer instanceof CultureFeed_Cdb_Item_Event) {
            $uivSourceUrl = $this->generateUivSourceUrl(
                $descriptionUpdated->getItemId(),
                $mainLanguageDetail->getTitle()
            );

            $longDescription .=
                "<p class=\"uiv-source\">Bron: <a href=\"{$uivSourceUrl}\">UiTinVlaanderen.be</a></p>";
        }

        $mainLanguageDetail->setLongDescription($longDescription);
        $mainLanguageDetail->setShortDescription($shortDescription);

        $offer->setDetails($details);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param string $offerId
     * @param string $title
     * @return string
     */
    private function generateUivSourceUrl($offerId, $title)
    {
        if (!$title) {
            $title = 'untitled';
        }

        $eventSlug = $this->slugger->slug($title);

        return 'http://www.uitinvlaanderen.be/agenda/e/' . $eventSlug . '/' . $offerId;
    }

    /**
     * @param AbstractBookingInfoUpdated $bookingInfoUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyBookingInfoUpdated(
        AbstractBookingInfoUpdated $bookingInfoUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($bookingInfoUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $bookingInfo = $bookingInfoUpdated->getBookingInfo();

        $this->updateCdbItemByBookingInfo($offer, $bookingInfo);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * Only works for events for now, as price is not supported on actors.
     *
     * @param \CultuurNet\UDB3\Offer\Events\AbstractPriceInfoUpdated $priceInfoUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyPriceInfoUpdated(
        AbstractPriceInfoUpdated $priceInfoUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument(
            $priceInfoUpdated->getItemId()
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $numberFormat = $this->numberFormatRepository->get('nl-BE');
        $currencyFormatter = new NumberFormatter($numberFormat, NumberFormatter::CURRENCY);

        $priceInfo = $priceInfoUpdated->getPriceInfo();

        // Create the cdb price object with a base price value.
        $basePrice = $priceInfo->getBasePrice();
        $cdbPrice = new \CultureFeed_Cdb_Data_Price($basePrice->getPrice()->toFloat());

        // Create a price description for each available language in the tariffs.
        $tariffs = $priceInfo->getTariffs();
        $descriptionStrings = [];
        foreach ($tariffs as $tariff) {
            $price = $tariff->getPrice()->toFloat();

            $currencyCode = $tariff->getCurrency()->getCode()->toNative();
            $currency = $this->currencyRepository->get($currencyCode);

            $tariffPrice = $currencyFormatter->formatCurrency((string) $price, $currency);

            $name = $tariff->getName();

            foreach ($name->getTranslationsIncludingOriginal() as $languageCode => $translation) {
                $descriptionStrings[$languageCode][] = $translation->toNative() . ': ' . $tariffPrice;
            }
        }

        // Add the base price to each of the price descriptions.
        $basePriceCurrencyCode = $basePrice->getCurrency()->getCode()->toNative();
        $basePriceCurrency = $this->currencyRepository->get($basePriceCurrencyCode);
        $basePriceFormatted = $currencyFormatter->formatCurrency(
            (string) $basePrice->getPrice()->toFloat(),
            $basePriceCurrency
        );

        // Get the current details and create an empty list for the new details.
        $details = $event->getDetails();
        $mainLanguageDetail = $details->getFirst();
        $updatedDetails = new CultureFeed_Cdb_Data_EventDetailList();

        // Create a list of all languages for which an eventdetail should exist.
        $detailLanguages = [];
        foreach ($details as $detail) {
            $detailLanguages[] = $detail->getLanguage();
        }
        $priceLanguages = array_keys($descriptionStrings);
        $languages = array_unique(array_merge($detailLanguages, $priceLanguages));

        // Create an eventdetail for each language, either based on an existing
        // eventdetail or a new one with the title of the main language detail.
        foreach ($languages as $language) {
            $detail = $details->getDetailByLanguage($language);
            if (!$detail) {
                $detail = $this->createOfferItemCdbDetail($event);
                $detail->setLanguage($language);
                $detail->setTitle($mainLanguageDetail->getTitle());
            }

            // Use the price object without description as the default for each
            // detail.
            $translatedCdbPrice = clone $cdbPrice;

            if (isset($descriptionStrings[$language])) {
                $descriptionParts = $descriptionStrings[$language];
            } else {
                $descriptionParts = [];
            }
            $basePriceDescription = $this->getBasePriceTranslation($language) . ': ' . $basePriceFormatted;
            array_unshift($descriptionParts, $basePriceDescription);
            $description = implode('; ', $descriptionParts);
            $translatedCdbPrice->setDescription($description);

            // Set a price object on each detail, with or without description.
            $detail->setPrice($translatedCdbPrice);

            // Add the new detail to the list of updated details.
            $updatedDetails->add($detail);
        }

        // Override the list of eventdetails with the list of updated details.
        $event->setDetails($updatedDetails);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param string $languageCode
     * @return string
     */
    private function getBasePriceTranslation($languageCode)
    {
        if (isset($this->basePriceTranslations[$languageCode])) {
            return $this->basePriceTranslations[$languageCode];
        }

        if (isset($this->basePriceTranslations['en'])) {
            return $this->basePriceTranslations['en'];
        }

        return 'Base tariff';
    }

    /**
     * @param AbstractContactPointUpdated $contactPointUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyContactPointUpdated(
        AbstractContactPointUpdated $contactPointUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($contactPointUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $contactPoint = $contactPointUpdated->getContactPoint();
        $this->updateCdbItemByContactPoint($offer, $contactPoint);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractLabelAdded $labelAdded
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyLabelAdded(
        AbstractLabelAdded $labelAdded,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($labelAdded->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $label = $labelAdded->getLabel()->__toString();
        $keyword = new CultureFeed_Cdb_Data_Keyword(
            $label,
            $labelAdded->getLabel()->isVisible()
        );

        // Always add the new keyword, even if it exists, so it can overwrite
        // the visibility if necessary.
        $offer->addKeyword($keyword);

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractLabelRemoved $labelRemoved
     * @param Metadata $metadata
     * @return CdbXmlDocument
     * @throws \Exception
     */
    public function applyLabelRemoved(
        AbstractLabelRemoved $labelRemoved,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($labelRemoved->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $originalKeywords = $offer->getKeywords();
        $keyword = $labelRemoved->getLabel()->__toString();
        $offer->deleteKeyword($keyword);
        $removedKeywords = array_diff($originalKeywords, $offer->getKeywords());

        if (!empty($removedKeywords)) {
            // Change the lastupdated attribute.
            $offer = $this->metadataCdbItemEnricher
                ->enrich($offer, $metadata);
        }

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractOrganizerUpdated $organizerUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyOrganizerUpdated(
        AbstractOrganizerUpdated $organizerUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument($organizerUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // load organizer from documentRepo & add to document
        $organizerCdbXml = $this->actorDocumentRepository->get($organizerUpdated->getOrganizerId());

        // It can happen that the organizer is not found
        if ($organizerCdbXml) {
            $actor = ActorItemFactory::createActorFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $organizerCdbXml->getCdbXml()
            );

            $organizer = new \CultureFeed_Cdb_Data_Organiser();
            $organizer->setCdbid($organizerUpdated->getOrganizerId());
            $organizer->setLabel($actor->getDetails()->getFirst()->getTitle());
            $organizer->setActor($actor);

            $event->setOrganiser($organizer);
        } else {
            $warning = 'Could not find organizer with id ' . $organizerUpdated->getOrganizerId();
            $warning .= ' when applying organizer updated on event ' . $organizerUpdated->getItemId() . '.';
            $this->logger->warning($warning);
        }

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
     * @return CdbXmlDocument
     */
    public function applyOrganizerDeleted(
        AbstractOrganizerDeleted $organizerDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument($organizerDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        // load organizer from documentRepo & add to document
        $organizerCdbXml = $this->actorDocumentRepository->get($organizerDeleted->getOrganizerId());

        // It can happen that the organizer is not found
        if ($organizerCdbXml) {
            $actor = ActorItemFactory::createActorFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $organizerCdbXml->getCdbXml()
            );
        }

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
     * @return CdbXmlDocument
     */
    public function applyTypicalAgeRangeUpdated(
        AbstractTypicalAgeRangeUpdated $ageRangeUpdated,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument($ageRangeUpdated->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $ageArray = explode('-', $ageRangeUpdated->getTypicalAgeRange());
        $ageFromString = $ageArray[0];
        $ageToString = $ageArray[1];
        if (filter_var($ageFromString, FILTER_VALIDATE_INT) === false) {
            $event->setAgeFrom();
        } else {
            $event->setAgeFrom((int) $ageFromString);
        }
        if (filter_var($ageToString, FILTER_VALIDATE_INT) === false) {
            $event->setAgeTo();
        } else {
            $event->setAgeTo((int) $ageToString);
        }

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
     * @return CdbXmlDocument
     */
    public function applyTypicalAgeRangeDeleted(
        AbstractTypicalAgeRangeDeleted $ageRangeDeleted,
        Metadata $metadata
    ) {
        $eventCdbXml = $this->getCdbXmlDocument($ageRangeDeleted->getItemId());

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $eventCdbXml->getCdbXml()
        );

        $event->setAgeFrom(null);
        $event->setAgeTo(null);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AddressUpdated $addressUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyAddressUpdated(
        AddressUpdated $addressUpdated,
        Metadata $metadata
    ) {
        $placeCdbXml = $this->getCdbXmlDocument($addressUpdated->getPlaceId());

        $place = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXml->getCdbXml()
        );

        $this->setCdbActorAddress($place, $addressUpdated->getAddress());

        $place = $this->metadataCdbItemEnricher
            ->enrich($place, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($place);
    }

    /**
     * @param AbstractFacilitiesUpdated $facilitiesUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyFacilitiesUpdated(
        AbstractFacilitiesUpdated $facilitiesUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->getCdbXmlDocument($facilitiesUpdated->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());


        $existingCategories = $offer->getCategories();
        $newCategoryList = new CultureFeed_Cdb_Data_CategoryList();

        // Add all the non-facility categories that should stay untouched to the new list.
        foreach ($existingCategories as $category) {
            if ($category->getType() !== CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FACILITY) {
                $newCategoryList->add($category);
            }
        };

        // Add new categories for the facilities, passed by the event, to the new list.
        foreach ($facilitiesUpdated->getFacilities() as $facility) {
            /* @var Facility $facility */
            $facilityCategory = new CultureFeed_Cdb_Data_Category(
                $facility->getDomain(),
                $facility->getId(),
                $facility->getLabel()
            );
            $newCategoryList->add($facilityCategory);
        }

        $offer->setCategories($newCategoryList);

        $offer = $this->metadataCdbItemEnricher->enrich($offer, $metadata);

        return $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($offer);
    }

    /**
     * Apply the imageAdded event.
     * @param AbstractImageAdded $imageAdded
     * @param MetaData $metadata
     * @return CdbXmlDocument
     */
    public function applyImageAdded(
        AbstractImageAdded $imageAdded,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->documentRepository->get($imageAdded->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        if (!$offer->getDetails()->getFirst()) {
            return;
        }

        $this->addImageToCdbItem($offer, $imageAdded->getImage());

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * Apply the imageUpdated event to udb2.
     * @param AbstractImageUpdated $imageUpdated
     * @param MetaData $metadata
     * @return CdbXmlDocument
     */
    public function applyImageUpdated(
        AbstractImageUpdated $imageUpdated,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->documentRepository->get($imageUpdated->getItemId());

        /** @var CultureFeed_Cdb_Item_Base $offer */
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $details = $offer->getDetails();
        /** @var \CultureFeed_Cdb_Data_Detail $detail */
        foreach ($details as $detail) {
            foreach ($detail->getMedia() as $file) {
                if ($this->fileMatchesMediaObject($file, $imageUpdated->getMediaObjectId())) {
                    $file->setTitle($imageUpdated->getDescription()->toNative());
                    $file->setCopyright($imageUpdated->getCopyrightHolder()->toNative());
                }
            }
        }

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractImageRemoved $imageRemoved
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyImageRemoved(
        AbstractImageRemoved $imageRemoved,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->documentRepository->get($imageRemoved->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $this->removeImageFromCdbItem($offer, $imageRemoved->getImage());

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractMainImageSelected $mainImageSelected
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyMainImageSelected(
        AbstractMainImageSelected $mainImageSelected,
        Metadata $metadata
    ) {
        $cdbXmlDocument = $this->documentRepository->get($mainImageSelected->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $this->selectCdbItemMainImage($offer, $mainImageSelected->getImage());

        // Change the lastupdated attribute.
        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * @param AbstractPublished $published
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyPublished(
        AbstractPublished $published,
        Metadata $metadata
    ) {
        return $this->setWorkflowStatus($published, $metadata, WorkflowStatus::READY_FOR_VALIDATION());
    }

    /**
     * @param AbstractApproved $approved
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyApproved(
        AbstractApproved $approved,
        Metadata $metadata
    ) {
        return $this->setWorkflowStatus($approved, $metadata, WorkflowStatus::APPROVED());
    }

    /**
     * @param AbstractEvent $rejectionEvent
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyRejected(
        AbstractEvent $rejectionEvent,
        Metadata $metadata
    ) {
        return $this->setWorkflowStatus($rejectionEvent, $metadata, WorkflowStatus::REJECTED());
    }

    /**
     * @param AudienceUpdated $audienceUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function applyAudienceUpdated(AudienceUpdated $audienceUpdated, Metadata $metadata)
    {
        $cdbXmlDocument = $this->documentRepository->get($audienceUpdated->getItemId());
        $event = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());
        $audienceType = $audienceUpdated->getAudience()->getAudienceType();

        $filter = new CategoryListFilter(
            new Not(new ID(self::SCHOOLS_CATEGORY_ID))
        );
        $categories = $filter->filter($event->getCategories());

        switch ($audienceType->getValue()) {
            case AudienceType::EVERYONE:
                $event->setPrivate(false);
                break;
            case AudienceType::MEMBERS:
                $event->setPrivate(true);
                break;
            case AudienceType::EDUCATION:
                $event->setPrivate(true);
                $targetAudience = new CultureFeed_Cdb_Data_Category(
                    'targetaudience',
                    self::SCHOOLS_CATEGORY_ID,
                    'Scholen'
                );
                $categories->add($targetAudience);
                break;
        }

        $event->setCategories($categories);

        // Change the lastupdated attribute.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param AbstractEvent $event
     * @param WorkflowStatus $status
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    public function setWorkflowStatus(AbstractEvent $event, Metadata $metadata, WorkflowStatus $status)
    {
        $cdbXmlDocument = $this->getCdbXmlDocument($event->getItemId());
        $offer = $this->parseOfferCultureFeedItem($cdbXmlDocument->getCdbXml());

        $offer->setWfStatus($status->getValue());

        if ($event instanceof AbstractPublished) {
            /** @var AbstractPublished $event */
            $availableFrom = $this->formatAvailable($event->getPublicationDate());
            $offer->setAvailableFrom($availableFrom);
        }

        $offer = $this->metadataCdbItemEnricher
            ->enrich($offer, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($offer);
    }

    /**
     * Set the location on the cdb event.
     *
     * @param LocationId $locationId
     * @param CultureFeed_Cdb_Item_Event $cdbEvent
     */
    private function setLocation(LocationId $locationId, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        // The location needs to exist as a place, so use this place to embed in the event.
        $placeCdbXml = $this->documentRepository->get($locationId->toNative());

        if ($placeCdbXml) {
            $place = ActorItemFactory::createActorFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $placeCdbXml->getCdbXml()
            );

            $this->setAddressFromPlace($place, $cdbEvent);
        } else {
            $warning = 'Could not find location with id ' . $locationId->toNative();
            $warning .= ' when setting location on event ' . $cdbEvent->getCdbId() . '.';
            $this->logger->warning($warning);
        }

        if (!$cdbEvent->getLocation() || !$cdbEvent->getLocation()->getAddress()->getPhysicalAddress()) {
            // We need to use a dummy location, otherwise the cdbxml will fail to load
            // when processing later events.
            $cdbEvent->setLocation($this->emptyLocation($locationId));
        }
    }

    private function setAddressFromPlace(CultureFeed_Cdb_Item_Actor $place, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $contactInfo = $place->getContactInfo();

        if (!$contactInfo) {
            $warning = 'unable to retrieve address from location with id ' . $place->getCdbId();
            $warning .= ', its cdbxml projection misses contact info';
            $this->logger->warning($warning);

            return;
        }

        $address = $contactInfo->getAddresses()[0];

        $location = new CultureFeed_Cdb_Data_Location($address);
        $location->setCdbid($place->getCdbid());
        // The name for the location can be taken from the title of the place details.
        $place->getDetails()->rewind();
        $location->setLabel($place->getDetails()->current()->getTitle());
        $cdbEvent->setLocation($location);

        $eventContactInfo = $cdbEvent->getContactInfo();
        if (is_null($eventContactInfo)) {
            $eventContactInfo = new CultureFeed_Cdb_Data_ContactInfo();
        }

        for ($index = 0; $index < count($eventContactInfo->getAddresses()); $index++) {
            $eventContactInfo->removeAddress($index);
        }

        $eventContactInfo->addAddress($address);

        $cdbEvent->setContactInfo($eventContactInfo);
    }

    /**
     * Creates a dummy location in case the referenced location can not be found.
     *
     * @param LocationId $locationId
     * @return CultureFeed_Cdb_Data_Location
     */
    private function emptyLocation(LocationId $locationId): CultureFeed_Cdb_Data_Location
    {
        $address = new \CultureFeed_Cdb_Data_Address();
        $address->setVirtualAddress(
            new \CultureFeed_Cdb_Data_Address_VirtualAddress('Onbekend')
        );

        $location = new CultureFeed_Cdb_Data_Location($address);
        $location->setCdbid($locationId->toNative());

        return $location;
    }

    /**
     * Set the Calendar on the cdb event.
     *
     * @param CalendarInterface $eventCalendar
     * @param CultureFeed_Cdb_Item_Event $cdbEvent
     */
    private function setCalendar(CalendarInterface $eventCalendar, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $calendar = $this->calendarConverter->toCdbCalendar($eventCalendar);

        if (isset($calendar)) {
            $cdbEvent->setCalendar($calendar);

            $formatter = new CalendarPlainTextFormatter();

            $calendarSummary = $formatter->format($calendar, 'lg');
            // CDXML does not expect any formatting so breaks are replaced with spaces
            $calendarSummary = str_replace(PHP_EOL, ' ', $calendarSummary);

            $eventDetails = $cdbEvent->getDetails();
            $eventDetails->rewind();
            $eventDetails->current()->setCalendarSummary($calendarSummary);
        }
    }

    /**
     * Update the cdb item based on a bookingInfo object.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param BookingInfo $bookingInfo
     */
    private function updateCdbItemByBookingInfo(
        CultureFeed_Cdb_Item_Base $cdbItem,
        BookingInfo $bookingInfo
    ) {
        // Add the booking Period.
        if ($cdbItem instanceof CultureFeed_Cdb_Item_Event) {
            $startDate = null;
            $endDate = null;

            if ($bookingInfo->getAvailabilityStarts()) {
                $startDate = $bookingInfo->getAvailabilityStarts()->getTimestamp();
            }
            if ($bookingInfo->getAvailabilityEnds()) {
                $endDate = $bookingInfo->getAvailabilityEnds()->getTimestamp();
            }

            $bookingPeriod = null;
            if (null !== $startDate || null !== $endDate) {
                $bookingPeriod = new CultureFeed_Cdb_Data_Calendar_BookingPeriod(
                    $startDate,
                    $endDate
                );
            }

            $cdbItem->setBookingPeriod($bookingPeriod);
        }

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
     * @param ContactPoint $contactPoint
     */
    private function updateCdbItemByContactPoint(
        CultureFeed_Cdb_Item_Base $cdbItem,
        ContactPoint $contactPoint
    ) {
        /* @var CultureFeed_Cdb_Item_Actor|CultureFeed_Cdb_Item_Event $cdbItem */
        $contactInfo = $cdbItem->getContactInfo() ? $cdbItem->getContactInfo() : new CultureFeed_Cdb_Data_ContactInfo();

        // Remove non-reservation phones and add new ones.
        foreach ($contactInfo->getPhones() as $phoneIndex => $phone) {
            /* @var CultureFeed_Cdb_Data_Phone $phone */
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
            /* @var CultureFeed_Cdb_Data_Url $url */
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

    /**
     * Modifies the an item to replace any of its categories that matches domain with the given category.
     *
     * @param CultureFeed_Cdb_Item_Base $item
     * @param Category $category
     */
    private function replaceCategoryByDomain(CultureFeed_Cdb_Item_Base $item, Category $category)
    {
        $filter = new CategoryListFilter(
            new Not(new Type($category->getDomain()))
        );

        $categories = $filter->filter($item->getCategories());

        $categories->add(
            new CultureFeed_Cdb_Data_Category(
                $category->getDomain(),
                $category->getId(),
                $category->getLabel()
            )
        );

        $item->setCategories($categories);
    }

    /**
     * Set the eventtype and theme on the event object.
     * @param \CultureFeed_Cdb_Item_Base $item
     * @param \CultuurNet\UDB3\Event\EventType $eventType
     * @param \CultuurNet\UDB3\Theme|NULL $theme
     */
    private function updateCategories(
        CultureFeed_Cdb_Item_Base $item,
        EventType $eventType,
        Theme $theme = null
    ) {
        $filter = new CategoryListFilter(
            new Not(
                new AnyOff(new Type('eventtype'), new Type('theme'))
            )
        );

        $categories = $filter->filter($item->getCategories());

        $categories->add(
            new CultureFeed_Cdb_Data_Category(
                'eventtype',
                $eventType->getId(),
                $eventType->getLabel()
            )
        );

        if ($theme) {
            $categories->add(
                new CultureFeed_Cdb_Data_Category(
                    'theme',
                    $theme->getId(),
                    $theme->getLabel()
                )
            );
        }

        $item->setCategories($categories);
    }

    /**
     * @param string $xmlString
     * @param string $namespace
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function updateEventFromCdbXml(
        $xmlString,
        $namespace,
        Metadata $metadata
    ) {
        $event = EventItemFactory::createEventFromCdbXml(
            $namespace,
            $xmlString
        );

        // Set the cdbid attribute on the embedded location if it's empty but
        // possible to derive from another attribute like eg. externalid.
        if ($event->getLocation() && empty($event->getLocation()->getCdbid())) {
            $locationCdbId = $this->eventCdbIdExtractor->getRelatedPlaceCdbId($event);

            if ($locationCdbId) {
                $location = $event->getLocation();
                $location->setCdbid($locationCdbId);
                $event->setLocation($location);
            }
        }

        // Set the cdbid attribute on the embedded organiser if it's empty but
        // possible to derive from another attribute like eg. externalid.
        if ($event->getOrganiser() && empty($event->getOrganiser()->getCdbid())) {
            $organiserCdbId = $this->eventCdbIdExtractor->getRelatedOrganizerCdbId($event);

            if ($organiserCdbId) {
                $organiser = $event->getOrganiser();
                $organiser->setCdbid($organiserCdbId);
                $event->setOrganiser($organiser);
            }
        }

        $event = $this->mergeShortAndLongDescription($event);

        // Add metadata like createdby, creationdate, etc to the event.
        $event = $this->metadataCdbItemEnricher
            ->enrich($event, $metadata);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);
    }

    /**
     * @param string $xmlString
     * @param string $namespace
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function updatePlaceFromCdbXml(
        $xmlString,
        $namespace,
        Metadata $metadata
    ) {
        $actor = ActorItemFactory::createActorFromCdbXml($namespace, $xmlString);

        // Add metadata like createdby, creationdate, etc to the actor.
        $actor = $this->metadataCdbItemEnricher
            ->enrich($actor, $metadata);

        $actor = $this->mergeShortAndLongDescription($actor);

        // Return a new CdbXmlDocument.
        $cdbxmlDocument = $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);

        return $cdbxmlDocument;
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @return CultureFeed_Cdb_Item_Base
     */
    private function mergeShortAndLongDescription(\CultureFeed_Cdb_Item_Base $cdbItem)
    {
        /* @var \CultureFeed_Cdb_Data_DetailList $updatedDetails */
        $currentDetails = $cdbItem->getDetails();
        $detailsClassName = get_class($currentDetails);
        $updatedDetails = new $detailsClassName();

        foreach ($currentDetails as $detail) {
            try {
                $mergedDescription = MergedDescription::fromCdbDetail($detail);
            } catch (\InvalidArgumentException $e) {
                // Detail has neither short nor long description.
                $updatedDetails->add($detail);
                continue;
            }

            $longDescription = $this->longDescriptionFilter->filter($mergedDescription->toNative());
            $shortDescription = $this->shortDescriptionFilter->filter($mergedDescription->toNative());
            $detail->setLongDescription($longDescription);
            $detail->setShortDescription($shortDescription);
            $updatedDetails->add($detail);
        }

        $cdbItem->setDetails($updatedDetails);

        return $cdbItem;
    }

    /**
     * @param EventCreated|PlaceCreated $offerCreated
     * @param \CultureFeed_Cdb_Item_Base $item
     */
    private function setItemAvailableFrom($offerCreated, \CultureFeed_Cdb_Item_Base $item)
    {
        if (!($offerCreated instanceof PlaceCreated) &&
            !($offerCreated instanceof EventCreated)
        ) {
            throw new \InvalidArgumentException(
                'Event with publication date should be of type EventCreated or PlaceCreated.'
            );
        }

        if (!is_null($offerCreated->getPublicationDate())) {
            $formatted = $this->formatAvailable(
                $offerCreated->getPublicationDate()
            );

            $item->setAvailableFrom($formatted);
        }
    }

    /**
     * @param CalendarInterface $calendar
     * @param \CultureFeed_Cdb_Item_Base $item
     */
    private function setItemAvailableToFromCalendar(
        CalendarInterface $calendar,
        \CultureFeed_Cdb_Item_Base $item
    ) {
        $availableTo = AvailableTo::createFromCalendar($calendar);
        $formattedAvailableTo = $this->formatAvailable(
            $availableTo->getAvailableTo()
        );

        $item->setAvailableTo($formattedAvailableTo);
    }

    /**
     * @param DateTimeInterface $available
     * @return string
     */
    private function formatAvailable(\DateTimeInterface $available)
    {
        return $this->dateFormatter->format($available->getTimestamp());
    }

    /**
     * @param string $id
     * @return CdbXmlDocument
     * @throws RuntimeException
     */
    private function getCdbXmlDocument($id)
    {
        $cdbXmlDocument = $this->documentRepository->get($id);

        if ($cdbXmlDocument == null) {
            throw new RuntimeException(
                'No document found for id ' . $id
            );
        }

        return $cdbXmlDocument;
    }

    /**
     * @param string $cdbXml
     * @return CultureFeed_Cdb_Item_Base
     *
     * @throws RuntimeException
     *   When the offer cdbxml can not be parsed.
     */
    private function parseOfferCultureFeedItem($cdbXml)
    {
        $namespaceUri = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

        $simpleXml = new \SimpleXMLElement($cdbXml, 0, false, $namespaceUri);
        $isActor = isset($simpleXml->actor);
        $isEvent = isset($simpleXml->event);

        if ($isActor) {
            $item = ActorItemFactory::createActorFromCdbXml($namespaceUri, $cdbXml);
        } elseif ($isEvent) {
            $item = EventItemFactory::createEventFromCdbXml($namespaceUri, $cdbXml);
        } else {
            throw new RuntimeException('Offer cdbxml is neither an actor nor an event.');
        }

        return $item;
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @return \CultureFeed_Cdb_Data_Detail
     */
    private function createOfferItemCdbDetail(CultureFeed_Cdb_Item_Base $item)
    {
        if ($item instanceof CultureFeed_Cdb_Item_Event) {
            return new CultureFeed_Cdb_Data_EventDetail();
        } elseif ($item instanceof CultureFeed_Cdb_Item_Actor) {
            return new CultureFeed_Cdb_Data_ActorDetail();
        } else {
            throw new RuntimeException('Cdb item is of an unknown type.');
        }
    }

    /**
     * Delete a given index on the cdb item.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param Image $image
     */
    protected function removeImageFromCdbItem(
        CultureFeed_Cdb_Item_Base $cdbItem,
        Image $image
    ) {
        $oldMedia = $this->getCdbItemMedia($cdbItem, $image->getLanguage());
        $mainImageRemoved = false;

        $newMedia = new CultureFeed_Cdb_Data_Media();
        foreach ($oldMedia as $key => $file) {
            if (!$this->fileMatchesMediaObject($file, $image->getMediaObjectId())) {
                $newMedia->add($file);
            } else {
                $mainImageRemoved = $mainImageRemoved || $file->isMain();
            }
        }

        $images = $newMedia->byMediaTypes($this->imageTypes);
        if ($mainImageRemoved && $images->count() > 0) {
            $images->rewind();
            $images->current()->setMain(true);
        }

        $details = $cdbItem->getDetails();
        $details->rewind();
        $details->current()->setMedia($newMedia);
    }

    /**
     * Select the main image for a cdb item.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param Image $image
     */
    protected function selectCdbItemMainImage(
        CultureFeed_Cdb_Item_Base $cdbItem,
        Image $image
    ) {
        $media = $this->getCdbItemMedia($cdbItem, $image->getLanguage());
        $mainImageId = $image->getMediaObjectId();

        foreach ($media as $file) {
            $file->setMain($this->fileMatchesMediaObject($file, $mainImageId));
        }
    }

    /**
     * Add an image to the cdb item.
     *
     * @param CultureFeed_Cdb_Item_Base $cdbItem
     * @param Image $image
     */
    protected function addImageToCdbItem(
        CultureFeed_Cdb_Item_Base $cdbItem,
        Image $image
    ) {
        $sourceUri = (string) $image->getSourceLocation();
        $uriParts = explode('/', $sourceUri);
        $media = $this->getCdbItemMedia($cdbItem, $image->getLanguage());

        $file = new CultureFeed_Cdb_Data_File();
        $file->setHLink($sourceUri);
        $file->setMediaType(CultureFeed_Cdb_Data_File::MEDIA_TYPE_PHOTO);

        // If there are no existing images the newly added one should be main.
        if ($media->byMediaTypes($this->imageTypes)->count() === 0) {
            $file->setMain();
        }

        // If the file name does not contain an extension, default to jpeg.
        $extension = 'jpeg';

        // If the file name does contain an extension, then normalize it.
        $filename = end($uriParts);
        if (false !== strpos($filename, '.')) {
            $fileparts = explode('.', $filename);
            $extension = strtolower(end($fileparts));
            if ($extension === 'jpg') {
                $extension = 'jpeg';
            }
        }

        $file->setFileType($extension);
        $file->setFileName($filename);

        $file->setCopyright((string) $image->getCopyrightHolder());
        $file->setTitle((string) $image->getDescription());

        $media->add($file);
    }

    /**
     * Get the media for a CDB item.
     *
     * If the items does not have any details, one will be created.
     *
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     *
     * @return CultureFeed_Cdb_Data_Media
     */
    protected function getCdbItemMedia(CultureFeed_Cdb_Item_Base $cdbItem, Language $language)
    {
        $details = $cdbItem->getDetails();
        $detail = $details->getDetailByLanguage($language->getCode());

        // Make sure a detail exists.
        if (empty($detail)) {
            $detail = $this->createOfferItemCdbDetail($cdbItem);
            $detail->setLanguage($language->getCode());

            // Take over title from main language, if possible.
            // The eventdetail title is a required element in cdbxml, so
            // we can't ignore it.
            $mainLanguageDetail = $details->getFirst();
            if ($mainLanguageDetail) {
                $detail->setTitle($mainLanguageDetail->getTitle());
            }

            $details->add($detail);
        }

        $media = $detail->getMedia();
        $media->rewind();
        return $media;
    }

    /**
     * Removes any addresses on the given actor and sets the given address
     * instead. Other contact information is preserved.
     *
     * @param CultureFeed_Cdb_Item_Actor $actor
     * @param Address $address
     */
    protected function setCdbActorAddress(CultureFeed_Cdb_Item_Actor $actor, Address $address)
    {
        $contactInfo = $actor->getContactInfo() ? $actor->getContactInfo() : new CultureFeed_Cdb_Data_ContactInfo();

        $previousAddresses = $contactInfo->getAddresses();
        foreach ($previousAddresses as $index => $previousAddress) {
            $contactInfo->removeAddress($index);
        }

        $cdbAddress = $this->addressFactory->fromUdb3Address($address);
        $contactInfo->addAddress($cdbAddress);

        $actor->setContactInfo($contactInfo);
    }

    /**
     * @param CultureFeed_Cdb_Data_File $file
     * @param UUID $mediaObjectId
     * @return bool
     */
    protected function fileMatchesMediaObject(
        CultureFeed_Cdb_Data_File $file,
        UUID $mediaObjectId
    ) {
        // Matching against the CDBID in the name of the image because
        // that's the only UDB3 reference in UDB2 we have.
        $matchesUDB3Image = !!strpos($file->getHLink(), (string) $mediaObjectId);

        $matchesUDB2Image = $mediaObjectId->sameValueAs($this->identifyFile($file));

        return $matchesUDB3Image || $matchesUDB2Image;
    }

    /**
     * @param CultureFeed_Cdb_Data_File $file
     *
     * @return UUID
     */
    private function identifyFile(CultureFeed_Cdb_Data_File $file)
    {
        $fileUri = $this->uriNormalizer->__invoke(Http::createFromString($file->getHLink())->withScheme('http'));

        $namespace = BaseUuid::uuid5(BaseUuid::NAMESPACE_DNS, $fileUri->getHost());
        return UUID::fromNative((string) BaseUuid::uuid5($namespace, (string) $fileUri));
    }
}
