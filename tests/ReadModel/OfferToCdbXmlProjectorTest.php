<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\Description\JsonLdDescriptionToCdbXmlLongDescriptionFilter;
use CultuurNet\UDB3\Cdb\Description\JsonLdDescriptionToCdbXmlShortDescriptionFilter;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated as EventCalendarUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated as EventFacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MainImageSelected;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Published as EventPublished;
use CultuurNet\UDB3\Event\Events\Moderation\Approved as EventApproved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected as EventRejected;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate as EventFlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate as EventFlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Location\Location;
use CultuurNet\UDB3\Location\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated as PlaceCalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated as PlaceContactPointUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated as PlaceFacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\Moderation\Approved as PlaceApproved;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected as PlaceRejected;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate as PlaceFlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate as PlaceFlaggedAsInappropriate;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Psr\Log\LoggerInterface;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\Person\Age;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class OfferToCdbXmlProjectorTest extends CdbXmlProjectorTestBase
{
    /**
     * @var OfferToCdbXmlProjector
     */
    protected $projector;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var CacheDocumentRepository
     */
    private $actorRepository;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->setCdbXmlFilesPath(__DIR__ . '/Repository/samples/');

        date_default_timezone_set('Europe/Brussels');

        $this->actorRepository = new CacheDocumentRepository($this->cache);

        $this->projector = (
        new OfferToCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            new MetadataCdbItemEnricher(
                new CdbXmlDateFormatter()
            ),
            $this->actorRepository,
            new CdbXmlDateFormatter(),
            new AddressFactory(),
            new JsonLdDescriptionToCdbXmlLongDescriptionFilter(),
            new JsonLdDescriptionToCdbXmlShortDescriptionFilter(),
            new CurrencyRepository(),
            new NumberFormatRepository(),
            new EventCdbIdExtractor(
                new ArrayMappingService(
                    [
                        'external-id-1' => '20ffb163-d5be-4a70-8f3a-fc853d17bbb4',
                    ]
                ),
                new ArrayMappingService(
                    [
                        'external-id-2' => 'c1fb0316-85a0-4dd3-9fa7-02410dff0e0f',
                    ]
                )
            ),
            [
                'nl' => 'Basistarief',
                'fr' => 'Tarif de base',
                'en' => 'Base tariff',
                'de' => 'Basisrate',
            ]
        ));

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->projector->setLogger($this->logger);

        $this->metadata = $this->getDefaultMetadata();
    }

    /**
     * @test
     * @dataProvider eventCreatedDataProvider
     * @param string $id
     * @param EventCreated $eventCreated
     * @param string $cdbXmlFileName
     */
    public function it_projects_event_created(
        $id,
        EventCreated $eventCreated,
        $cdbXmlFileName
    ) {
        $placeCreated = new PlaceCreated(
            $this->getPlaceId(),
            new Language('nl'),
            new Title('Bibberburcht'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('Bondgenotenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage($this->getPlaceId(), $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $domainMessage = $this->createDomainMessage($id, $eventCreated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile($cdbXmlFileName)
        );

        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function eventCreatedDataProvider()
    {
        $timestamps = $this->getTimestamps();

        $address = new Address(
            new Street('Bondgenotenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $location = new Location(
            $this->getPlaceId(),
            new StringLiteral('Bibberburcht'),
            $address
        );

        return [
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                new EventCreated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Language('nl'),
                    new Title('Griezelfilm of horror'),
                    new EventType('0.50.6.0.0', 'film'),
                    $location,
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                        $timestamps
                    ),
                    new Theme('1.7.6.0.0', 'Griezelfilm of horror')
                ),
                'event.xml',
            ],
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                new EventCreated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Language('nl'),
                    new Title('Griezelfilm of horror'),
                    new EventType('0.50.6.0.0', 'film'),
                    $location,
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                        \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                        $timestamps
                    ),
                    new Theme('1.7.6.0.0', 'Griezelfilm of horror'),
                    \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', '2016-04-23T15:30:06')
                ),
                'event-with-publication-date.xml',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_logs_warning_when_event_created_with_missing_location()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $timestamps = $this->getTimestamps();

        $placeId = UUID::generateAsString();
        $unknownPlaceId = UUID::generateAsString();

        $address = new Address(
            new Street('Bondgenotenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $location = new Location(
            $unknownPlaceId,
            new StringLiteral('Bibberburcht'),
            $address
        );

        $placeCreated = new PlaceCreated(
            $placeId,
            new Language('nl'),
            new Title('Bibberburcht'),
            new EventType('0.50.4.0.0', 'concert'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage($id, $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $event = new EventCreated(
            $id,
            new Language('nl'),
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            $location,
            new Calendar(
                CalendarType::MULTIPLE(),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                $timestamps
            ),
            new Theme('1.7.6.0.0', 'Griezelfilm of horror')
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-without-location.xml')
        );

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find location with id ' . $unknownPlaceId . ' when setting location on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_remove_all_labels_when_an_event_gets_copied()
    {
        $originalEventId = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventId = '8b1855f7-7f11-4653-9fbb-f5f4611f7960';

        $cdbXmlDocument = new CdbXmlDocument(
            $originalEventId,
            $this->loadCdbXmlFromFile('event-copied-original.xml')
        );
        $this->repository->save($cdbXmlDocument);

        $organizerId = 'ORG-123';
        $organizerCdbxml = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-uitpas-keyword.xml')
        );
        $this->actorRepository->save($organizerCdbxml);

        $eventCopied = new EventCopied(
            $eventId,
            $originalEventId,
            new Calendar(CalendarType::PERMANENT())
        );

        $metadata = new Metadata(
            [
                'user_nick' => '2dotstwice',
                'user_email' => 'info@2dotstwice.be',
                'user_id' => '65000e81-2860-4120-a97e-1dca743892e5',
                'request_time' => '1460710958',
                'id' => 'http://foo.be/item/8b1855f7-7f11-4653-9fbb-f5f4611f7960',
            ]
        );

        $domainMessage = $this->createDomainMessage(
            $eventId,
            $eventCopied,
            $metadata
        );

        $this->projector->handle($domainMessage);

        $cdbXmlDocument = $this->repository->get($eventId);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $eventId,
            $this->loadCdbXmlFromFile('event-copied.xml')
        );

        $this->assertEquals($expectedCdbXmlDocument, $cdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_event_imported_from_udb2()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $eventImportedFromUdb2,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-imported.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_merge_different_short_and_long_description_when_projecting_events_imported_from_udb2()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-namespaced-with-short-description-different-from-long.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $eventImportedFromUdb2,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-imported-with-short-description-merged-into-long-description.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_not_merge_short_and_long_description_the_short_description_is_already_included_in_the_long_description()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-imported-with-short-description-merged-into-long-description.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $eventImportedFromUdb2,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-imported-with-short-description-merged-into-long-description.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_event_updated_from_udb2()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(9), new Age(12))
                )
            )
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-namespaced-with-short-description-different-from-long.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )
            ->expect('event-imported-with-short-description-merged-into-long-description.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_merge_different_short_and_long_description_when_projecting_events_updated_from_udb2()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(9), new Age(12))
                )
            )
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-namespaced.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )
            ->expect('event-imported.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_event_updated_from_udb2_and_major_info_updated_without_theme()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-imported-with-categories.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )
            ->apply(
                new MajorInfoUpdated(
                    $this->getEventId(),
                    new Title("Griezelfilm of horror"),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Location(
                        $this->getPlaceId(),
                        new StringLiteral('Bibberburcht'),
                        new Address(
                            new Street('Bondgenotenlaan 1'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            Country::fromNative('BE')
                        )
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    null
                )
            )
            ->expect('event-with-categories-and-major-info-update-without-theme.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_event_updated_from_udb2_and_major_info_updated_wit_theme()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-imported-with-categories.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )
            ->apply(
                new MajorInfoUpdated(
                    $this->getEventId(),
                    new Title("Griezelfilm of horror"),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Location(
                        $this->getPlaceId(),
                        new StringLiteral('Bibberburcht'),
                        new Address(
                            new Street('Bondgenotenlaan 1'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            Country::fromNative('BE')
                        )
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    new Theme('1.8.2.0.0', 'Jazz en blues')
                )
            )
            ->expect('event-with-categories-and-major-info-update.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_adds_place_and_organizer_cdbid_based_on_external_id_for_events_imported_from_udb2()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-with-place-and-organizer-with-external-ids.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage(
            $id,
            $eventImportedFromUdb2,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-place-and-organizer-with-external-ids-and-cdbids.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_adds_place_and_organizer_cdbid_based_on_external_id_for_events_updated_from_udb2()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-with-place-and-organizer-with-external-ids.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )
            ->expect('event-with-place-and-organizer-with-external-ids-and-cdbids.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_the_deletion_of_an_event()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventDeleted(
                    $this->getEventId()
                )
            )
            ->expect('event-deleted.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider placeCreatedDataProvider
     * @param string $id
     * @param PlaceCreated $placeCreated
     * @param string $cdbXmlFileName
     */
    public function it_projects_place_created(
        $id,
        PlaceCreated $placeCreated,
        $cdbXmlFileName
    ) {
        $domainMessage = $this->createDomainMessage($id, $placeCreated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile($cdbXmlFileName)
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function placeCreatedDataProvider()
    {
        $address = new Address(
            new Street('Bondgenotenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        return [
            [
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                new PlaceCreated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Language('nl'),
                    new Title('My Place'),
                    new EventType('0.50.4.0.0', 'concert'),
                    $address,
                    new Calendar(CalendarType::PERMANENT())
                ),
                'place.xml',
            ],
            [
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                new PlaceCreated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Language('nl'),
                    new Title('My Place'),
                    new EventType('0.50.4.0.0', 'concert'),
                    $address,
                    new Calendar(CalendarType::PERMANENT()),
                    null,
                    \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', '2016-04-23T15:30:06')
                ),
                'place-with-publication-date.xml',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_project_places_imported_from_udb2_as_actors()
    {
        $actorImportedFromUDB2 = new PlaceImportedFromUDB2(
            '061C13AC-A15F-F419-D8993D68C9E94548',
            file_get_contents(__DIR__ . '/Repository/samples/place-actor.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $id = $actorImportedFromUDB2->getActorId();

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-actor-generated.xml')
        );

        $domainMessage = $this->createDomainMessage($id, $actorImportedFromUDB2, $this->metadata);

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_merge_short_and_long_description_when_projecting_imported_places_and_short_is_not_included_in_long()
    {
        $actorImportedFromUDB2 = new PlaceImportedFromUDB2(
            '061C13AC-A15F-F419-D8993D68C9E94548',
            file_get_contents(
                __DIR__ . '/Repository/samples/place-actor-with-short-description-different-from-long.xml'
            ),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $id = $actorImportedFromUDB2->getActorId();

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-actor-generated-with-short-description-merged-into-long.xml')
        );

        $domainMessage = $this->createDomainMessage($id, $actorImportedFromUDB2, $this->metadata);

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_project_places_updated_in_udb2_as_actors()
    {
        $actorImportedFromUDB2 = new PlaceUpdatedFromUDB2(
            '061C13AC-A15F-F419-D8993D68C9E94548',
            file_get_contents(__DIR__ . '/Repository/samples/place-actor.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $id = $actorImportedFromUDB2->getActorId();

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-actor-generated.xml')
        );

        $domainMessage = $this->createDomainMessage($id, $actorImportedFromUDB2, $this->metadata);

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_merge_short_and_long_description_when_projecting_places_updated_in_udb2_and_short_is_not_included_in_long()
    {
        $actorImportedFromUDB2 = new PlaceUpdatedFromUDB2(
            '061C13AC-A15F-F419-D8993D68C9E94548',
            file_get_contents(
                __DIR__ . '/Repository/samples/place-actor-with-short-description-different-from-long.xml'
            ),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $id = $actorImportedFromUDB2->getActorId();

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-actor-generated-with-short-description-merged-into-long.xml')
        );

        $domainMessage = $this->createDomainMessage($id, $actorImportedFromUDB2, $this->metadata);

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_deletion_of_a_place()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new PlaceDeleted(
                    $this->getPlaceId()
                )
            )
            ->expect('place-deleted.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param string $id
     * @param string $cdbXmlType
     */
    public function it_projects_title_translations(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new TitleTranslated(
                    $id,
                    new Language('en'),
                    new Title('Horror movie')
                )
            )
            ->expect($cdbXmlType . '-with-title-translated-to-en.xml')
            ->apply(
                new TitleTranslated(
                    $id,
                    new Language('en'),
                    new Title('Horror movie updated')
                )
            )
            ->expect($cdbXmlType . '-with-title-translated-to-en-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param string $id
     * @param string $cdbXmlType
     */
    public function it_should_update_the_main_language_title_detail_when_a_title_updated_event_occurs(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new TitleUpdated(
                    $id,
                    new Title('Nieuwe titel')
                )
            )
            ->expect($cdbXmlType . '-with-title-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_projects_description_translations(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new DescriptionTranslated(
                    $id,
                    new Language('en'),
                    new \CultuurNet\UDB3\Description('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.')
                )
            )
            ->expect($cdbXmlType . '-with-description-translated-to-en.xml')
            ->apply(
                new DescriptionTranslated(
                    $id,
                    new Language('en'),
                    new \CultuurNet\UDB3\Description('Description updated.')
                )
            )
            ->expect($cdbXmlType . '-with-description-translated-to-en-updated.xml');

        $this->execute($test);
    }

    public function descriptionUpdatesProvider()
    {
        $genericOfferData = $this->genericOfferTestDataProvider();

        $descriptionUpdates = [
            ['description-1'],
            ['description-2'],
            ['description-3'],
        ];

        $fullTestData = [];

        foreach ($genericOfferData as $genericOffer) {
            foreach ($descriptionUpdates as $descriptionUpdate) {
                $fullTestData[] = array_merge(
                    $genericOffer,
                    $descriptionUpdate
                );
            }
        }

        return $fullTestData;
    }

    /**
     * @test
     * @dataProvider descriptionUpdatesProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     * @param $exampleId
     *
     * @group issue-III-1126
     */
    public function it_projects_description_updates(
        OfferType $offerType,
        $id,
        $cdbXmlType,
        $exampleId
    ) {
        $test = $this->given($offerType)
            ->apply(
                new DescriptionUpdated(
                    $id,
                    new \CultuurNet\UDB3\Description('Initial description')
                )
            )
            ->apply(
                new DescriptionUpdated(
                    $id,
                    new \CultuurNet\UDB3\Description(file_get_contents(__DIR__ .'/Repository/samples/description/' . $exampleId . '.txt'))
                )
            )
            ->expect('description/' . $cdbXmlType . '-with-' . $exampleId . '.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_projects_label_events(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new LabelAdded(
                    $id,
                    new Label('foobar')
                )
            )
            ->expect($cdbXmlType . '-with-keyword.xml')
            ->apply(
                new LabelAdded(
                    $id,
                    new Label('foobar')
                )
            )
            ->expect($cdbXmlType . '-with-keyword.xml')
            ->apply(
                new LabelAdded(
                    $id,
                    new Label('foobar', false)
                )
            )
            ->expect($cdbXmlType . '-with-keyword-visible-false.xml')
            ->apply(
                new LabelRemoved(
                    $id,
                    new Label('foobar')
                )
            )
            ->expect($cdbXmlType . '.xml')
            ->apply(
                new LabelRemoved(
                    $id,
                    new Label('foobar')
                )
            )
            ->expect($cdbXmlType . '.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_projects_booking_info_events(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new BookingInfoUpdated(
                    $id,
                    new BookingInfo(
                        'http://tickets.example.com',
                        'Tickets on Example.com',
                        '+32 666 666',
                        'tickets@example.com',
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2014-01-31T13:00:00+01:00'),
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2014-02-20T16:00:00+01:00')
                    )
                )
            )
            ->expect($cdbXmlType . '-booking-info-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @group issue-III-1778
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_removes_bookingperiod_when_booking_availability_is_removed(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new BookingInfoUpdated(
                    $id,
                    new BookingInfo(
                        'http://tickets.example.com',
                        'Tickets on Example.com',
                        '+32 666 666',
                        'tickets@example.com',
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2014-01-31T13:00:00+01:00'),
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2014-02-20T16:00:00+01:00')
                    )
                )
            )
            ->apply(
                new BookingInfoUpdated(
                    $id,
                    new BookingInfo(
                        'http://tickets.example.com',
                        'Tickets on Example.com',
                        '+32 666 666',
                        'tickets@example.com',
                        null,
                        null
                    )
                )
            )
            ->expect($cdbXmlType . '-booking-info-availability-removed.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_price_info_events_on_events()
    {
        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );

        $priceInfo = $priceInfo
            ->withExtraTariff(
                new Tariff(
                    new MultilingualString(
                        new Language('nl'),
                        new StringLiteral('Werkloze dodo kwekers')
                    ),
                    Price::fromFloat(7.755),
                    Currency::fromNative('EUR')
                )
            )
            ->withExtraTariff(
                new Tariff(
                    new MultilingualString(
                        new Language('nl'),
                        new StringLiteral('Seniele senioren')
                    ),
                    new Price(0),
                    Currency::fromNative('EUR')
                )
            );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new PriceInfoUpdated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    $priceInfo
                )
            )
            ->expect('event-with-price-info.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_multilingual_price_info_events_on_events()
    {
        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );

        $priceInfo = $priceInfo
            ->withExtraTariff(
                new Tariff(
                    (new MultilingualString(
                        new Language('nl'),
                        new StringLiteral('Werkloze dodo kwekers')
                    ))
                        ->withTranslation(
                            new Language('fr'),
                            new StringLiteral('Werkloze dodo kwekers FR')
                        )
                        ->withTranslation(
                            new Language('en'),
                            new StringLiteral('Werkloze dodo kwekers EN')
                        ),
                    Price::fromFloat(7.755),
                    Currency::fromNative('EUR')
                )
            )
            ->withExtraTariff(
                new Tariff(
                    (new MultilingualString(
                        new Language('nl'),
                        new StringLiteral('Seniele senioren')
                    ))
                        ->withTranslation(
                            new Language('fr'),
                            new StringLiteral('Seniele senioren FR')
                        )
                        ->withTranslation(
                            new Language('en'),
                            new StringLiteral('Seniele senioren EN')
                        ),
                    new Price(0),
                    Currency::fromNative('EUR')
                )
            );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TitleTranslated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Language('fr'),
                    new StringLiteral('Titel FR')
                )
            )
            ->apply(
                new PriceInfoUpdated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    $priceInfo
                )
            )
            ->expect('event-with-multilingual-price-info.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_events_with_price_info_that_does_not_have_tariffs()
    {
        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(0.20),
                Currency::fromNative('EUR')
            )
        );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new PriceInfoUpdated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    $priceInfo
                )
            )
            ->expect('event-with-price-info-and-no-tariffs.xml');

        $this->execute($test);
    }

    /**
     * @test
     *
     * @group issue-III-1618
     */
    public function it_does_not_loose_free_entrance_priceinfo_on_further_modifications()
    {
        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(0.0),
                Currency::fromNative('EUR')
            )
        );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new PriceInfoUpdated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    $priceInfo
                )
            )
            ->apply(
                new Published(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new \DateTimeImmutable('2016-11-15 19:58')
                )
            )
            ->expect('event-with-free-entrance.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_projects_contact_point_events(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new ContactPointUpdated(
                    $id,
                    new ContactPoint(
                        array('+32 666 666'),
                        array('tickets@example.com'),
                        array('http://tickets.example.com')
                    )
                )
            )
            ->expect($cdbXmlType . '-contact-point-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_projects_basic_image_events(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('title'),
            new CopyrightHolder('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('nl')
        );

        $test = $this->given($offerType)
            ->apply(
                new ImageAdded($id, $image)
            )
            ->expect($cdbXmlType . '-with-image.xml')
            ->apply(
                new ImageUpdated(
                    $id,
                    $image->getMediaObjectId(),
                    new StringLiteral('title updated'),
                    new StringLiteral('John Doe')
                )
            )
            ->expect($cdbXmlType . '-with-image-updated.xml')
            ->apply(
                new ImageRemoved($id, $image)
            )
            ->expect($cdbXmlType . '.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider genericOfferTestDataProvider
     *
     * @param OfferType $offerType
     * @param $id
     * @param $cdbXmlType
     */
    public function it_projects_main_image_selected_events(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $firstImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('title'),
            new CopyrightHolder('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('nl')
        );

        $secondImage = new Image(
            new UUID('9554d6f6-bed1-4303-8d42-3fcec4601e0e'),
            new MIMEType('image/jpg'),
            new Description('Beep Boop'),
            new CopyrightHolder('Noo Idee'),
            Url::fromNative('http://foo.bar/media/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg'),
            new Language('nl')
        );

        $test = $this->given($offerType)
            ->apply(
                new ImageAdded($id, $firstImage)
            )
            ->apply(
                new ImageAdded($id, $secondImage)
            )
            ->apply(
                new MainImageSelected($id, $secondImage)
            )
            ->expect($cdbXmlType . '-with-images.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_make_the_oldest_remaining_image_main_when_the_original_main_image_is_removed()
    {
        $id = $this->getEventId();
        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-with-images.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $originalMainImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('title'),
            new CopyrightHolder('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('nl')
        );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                $eventImportedFromUdb2
            )
            ->apply(
                new ImageRemoved($id, $originalMainImage)
            )
            ->expect('event-with-original-main-image-removed.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_keep_images_imported_from_udb2_when_you_add_an_udb3_image()
    {
        $id = $this->getEventId();
        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-with-images.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $udb3Image = new Image(
            new UUID('c0c96570-3b3c-4d3f-9d82-c26b290e6c12'),
            new MIMEType('image/png'),
            new Description('My best selfie.'),
            new CopyrightHolder('Duck Face'),
            Url::fromNative('http://foo.bar/media/c0c96570-3b3c-4d3f-9d82-c26b290e6c12.png'),
            new Language('nl')
        );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                $eventImportedFromUdb2
            )
            ->apply(
                new ImageAdded($id, $udb3Image)
            )
            ->expect('event-with-udb2-and-udb3-images.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_select_an_udb2_image_as_main_by_uuid()
    {
        $id = $this->getEventId();
        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-with-udb2-images.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $udb2Image = new Image(
            new UUID('bb9dce43-6a6f-5003-bfde-b4a71342a47a'),
            new MIMEType('image/png'),
            new Description('title'),
            new CopyrightHolder('John Doe'),
            Url::fromNative('http://udb.twee/media/img_001.png'),
            new Language('nl')
        );

        $test = $this->given(OfferType::EVENT())
            ->apply(
                $eventImportedFromUdb2
            )
            ->apply(
                new MainImageSelected($id, $udb2Image)
            )
            ->expect('event-with-new-udb2-main-image.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_organizer_events()
    {
        // Create an organizer.
        $organizerId = 'ORG-123';
        $organizerCdbxml = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->actorRepository->save($organizerCdbxml);

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new OrganizerUpdated(
                    $this->getEventId(),
                    $organizerId
                )
            )
            ->expect('event-with-organizer.xml')
            ->apply(
                new OrganizerDeleted(
                    $this->getEventId(),
                    $organizerId
                )
            )->expect('event.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_logs_a_warning_when_organizer_updated_but_organizer_not_found()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new OrganizerUpdated(
                    $this->getEventId(),
                    'ORG-123'
                )
            )
            ->expect('event-without-organizer.xml');

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find organizer with id ORG-123 when applying organizer updated on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_update_the_address_and_preserve_other_contact_info_when_a_place_has_its_address_updated()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new PlaceContactPointUpdated(
                    $this->getPlaceId(),
                    new ContactPoint(
                        ['+32 444 44 44 44'],
                        ['test@foo.bar'],
                        ['https://foo.bar']
                    )
                )
            )
            ->apply(
                new AddressUpdated(
                    $this->getPlaceId(),
                    new Address(
                        new Street('Kerkstraat 69'),
                        new PostalCode('1000'),
                        new Locality('Brussel'),
                        Country::fromNative('DE')
                    )
                )
            )
            ->expect('place-with-contact-point-and-address-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_an_updated_list_of_categories_when_place_facilities_have_changed()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new PlaceFacilitiesUpdated(
                    $this->getPlaceId(),
                    [
                        new Facility('3.13.3.0.0', 'Brochure beschikbaar in braille'),
                        new Facility('3.17.3.0.0', 'Ondertiteling'),
                        new Facility('3.17.1.0.0', 'Ringleiding'),
                    ]
                )
            )->expect('place-with-facilities.xml')
            ->apply(
                new PlaceFacilitiesUpdated(
                    $this->getPlaceId(),
                    [
                        new Facility('3.13.2.0.0', 'Audiodescriptie'),
                        new Facility('3.17.3.0.0', 'Ondertiteling'),
                        new Facility('3.17.1.0.0', 'Ringleiding'),
                    ]
                )
            )->expect('place-with-updated-facilities.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_an_updated_list_of_categories_when_event_facilities_have_changed()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventFacilitiesUpdated(
                    $this->getEventId(),
                    [
                        new Facility('3.13.3.0.0', 'Brochure beschikbaar in braille'),
                        new Facility('3.17.3.0.0', 'Ondertiteling'),
                        new Facility('3.17.1.0.0', 'Ringleiding'),
                    ]
                )
            )->expect('event-with-facilities.xml')
            ->apply(
                new EventFacilitiesUpdated(
                    $this->getEventId(),
                    [
                        new Facility('3.13.2.0.0', 'Audiodescriptie'),
                        new Facility('3.17.3.0.0', 'Ondertiteling'),
                        new Facility('3.17.1.0.0', 'Ringleiding'),
                    ]
                )
            )->expect('event-with-updated-facilities.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_ageFrom_and_ageTo_when_updating_typical_age_range_that_has_both_a_lower_and_upper_boundary()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(9), new Age(12))
                )
            )
            ->expect('event-with-age-from-and-age-to.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_ageFrom_and_remove_ageTo_when_updating_typical_age_range_that_only_has_a_lower_boundary()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(9), new Age(12))
                )
            )
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(10))
                )
            )
            ->expect('event-with-age-from.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_ageTo_and_remove_ageFrom_when_updating_typical_age_range_that_only_has_an_upper_boundary()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(9), new Age(12))
                )
            )
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(null, new Age(18))
                )
            )
            ->expect('event-with-age-to.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_ageTo_and_ageFrom_when_updating_typical_age_range_with_zero_value_boundaries()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(0), new Age(0))
                )
            )
            ->expect('event-with-zero-value-age-from-and-to.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_remove_ageTo_and_ageFrom_when_projecting_a_typical_age_range_update_without_boundaries()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(1), new Age(111))
                )
            )->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange()
                )
            )
            ->expect('event.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_remove_ageTo_and_ageFrom_when_projecting_a_typical_age_range_deleted_event()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    new AgeRange(new Age(1), new Age(111))
                )
            )->apply(
                new TypicalAgeRangeDeleted(
                    $this->getEventId()
                )
            )
            ->expect('event.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_event_major_info_updated()
    {
        $unknownPlaceID = UUID::generateAsString();

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new MajorInfoUpdated(
                    $this->getEventId(),
                    new Title("Nieuwe titel"),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Location(
                        $this->getPlaceId(),
                        new StringLiteral('Bibberburcht'),
                        new Address(
                            new Street('Bondgenotenlaan 1'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            Country::fromNative('BE')
                        )
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    new Theme('1.8.2.0.0', 'Jazz en blues')
                )
            )
            ->expect('event-with-major-info-updated.xml')
            ->apply(
                new MajorInfoUpdated(
                    $this->getEventId(),
                    new Title("Nieuwe titel"),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Location(
                        $unknownPlaceID,
                        new StringLiteral('Somewhere over the rainbow'),
                        new Address(
                            new Street('Kerkstraat 69'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            Country::fromNative('BE')
                        )
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    new Theme('1.8.2.0.0', 'Jazz en blues')
                )
            )
            ->expect('event-with-major-info-updated-without-location.xml');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Could not find location with id ' . $unknownPlaceID .' when setting location on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_preserve_unrelated_contact_info_when_applying_major_info_updated_on_event()
    {
        $this->createKerkPlace();

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new ContactPointUpdated(
                    $this->getEventId(),
                    new ContactPoint(
                        ['+32 444 44 44 44'],
                        ['test@foo.bar'],
                        ['https://foo.bar']
                    )
                )
            )
            ->apply(
                new MajorInfoUpdated(
                    $this->getEventId(),
                    new Title("Nieuwe titel"),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Location(
                        $this->getKerkPlaceId(),
                        new StringLiteral('Somewhere over the rainbow'),
                        new Address(
                            new Street('Kerkstraat 69'),
                            new PostalCode('1000'),
                            new Locality('Brussel'),
                            Country::fromNative('DE')
                        )
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    new Theme('1.8.2.0.0', 'Jazz en blues')
                )
            )
            ->expect('event-with-contact-point-and-major-info-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_preserve_unrelated_contact_info_when_applying_major_info_updated_on_place()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new PlaceContactPointUpdated(
                    $this->getPlaceId(),
                    new ContactPoint(
                        ['+32 444 44 44 44'],
                        ['test@foo.bar'],
                        ['https://foo.bar']
                    )
                )
            )
            ->apply(
                new PlaceMajorInfoUpdated(
                    $this->getPlaceId(),
                    new Title("Bibberburcht"),
                    new EventType('8.4.0.0.0', 'Galerie'),
                    new Address(
                        new Street('Kerkstraat 69'),
                        new PostalCode('1000'),
                        new Locality('Brussel'),
                        Country::fromNative('DE')
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    new Theme('1.0.1.0.0', 'Schilderkunst')
                )
            )
            ->expect('place-with-contact-point-and-major-info-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_event_without_theme_major_info_updated()
    {
        $this->createEvent(false);
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // add the major info to the event.
        $majorInfoUpdated = new MajorInfoUpdated(
            $id,
            new Title("Nieuwe titel"),
            new EventType("0.50.4.0.0", "concert"),
            new Location(
                $this->getPlaceId(),
                new StringLiteral('Bibberburcht'),
                new Address(
                    new Street('Bondgenotenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            ),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.2.0.0', 'Jazz en blues')
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $majorInfoUpdated,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-major-info-updated.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_event_major_info_updated_with_removed_theme()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $placeId = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

        // add the major info to the event.
        $majorInfoUpdated = new MajorInfoUpdated(
            $id,
            new Title("Nieuwe titel"),
            new EventType("id", "label"),
            new Location(
                $placeId,
                new StringLiteral('Bibberburcht'),
                new Address(
                    new Street('Bondgenotenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            ),
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $majorInfoUpdated,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-major-info-updated-without-theme.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_place_major_info_updated()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new PlaceMajorInfoUpdated(
                    $this->getPlaceId(),
                    new Title("Monochrome Rainbow Rave"),
                    new EventType("8.4.0.0.0", "Galerie"),
                    new Address(
                        new Street('Kerkstraat 69'),
                        new PostalCode('1000'),
                        new Locality('Brussel'),
                        Country::fromNative('DE')
                    ),
                    new Calendar(CalendarType::PERMANENT()),
                    new Theme('1.0.1.0.0', 'Schilderkunst')
                )
            )
            ->expect('place-with-major-info-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_event_location_updated()
    {
        $placeId = 'ed138027-0d17-4b8e-8bfd-b547c96e2771';

        $address = new Address(
            new Street('Horststraat 28'),
            new PostalCode('3220'),
            new Locality('Holsbeek'),
            Country::fromNative('BE')
        );

        $placeCreated = new PlaceCreated(
            $placeId,
            new Language('nl'),
            new Title('Kasteel van Horst'),
            new EventType('0.1.2', 'kasteel'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage(
            $placeId,
            $placeCreated,
            $this->metadata
        );
        $this->projector->handle($domainMessage);

        $test = $this->given(OfferType::EVENT())
            ->apply(
                new LocationUpdated(
                    $this->getEventId(),
                    new LocationId($placeId)
                )
            )
            ->expect('event-with-location-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_event_calendar_updated()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventCalendarUpdated(
                    $this->getEventId(),
                    $this->getMultipleCalendar()
                )
            )
            ->expect('event-with-calendar-updated.xml');

        $this->execute($test);
    }

    /**
     * @return Calendar
     */
    private function getMultipleCalendar()
    {
        $startDatePeriod1 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T09:00:00+01:00');
        $endDatePeriod1 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-01T16:00:00+01:00');

        $startDatePeriod2 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-03T09:00:00+01:00');
        $endDatePeriod2 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-10T16:00:00+01:00');

        $timeStamps = [
            new Timestamp(
                $startDatePeriod1,
                $endDatePeriod1
            ),
            new Timestamp(
                $startDatePeriod2,
                $endDatePeriod2
            ),
        ];

        return new Calendar(
            CalendarType::MULTIPLE(),
            $startDatePeriod1,
            $endDatePeriod2,
            $timeStamps,
            []
        );
    }

    /**
     * @test
     */
    public function it_projects_place_calendar_updated()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new PlaceCalendarUpdated(
                    $this->getPlaceId(),
                    $this->getPermanentCalendar()
                )
            )
            ->expect('place-with-calendar-updated.xml');

        $this->execute($test);
    }

    /**
     * @return Calendar
     */
    private function getPermanentCalendar()
    {
        $openingHours = [
            new OpeningHour(
                new OpeningTime(
                    new Hour(9),
                    new Minute(0)
                ),
                new OpeningTime(
                    new Hour(17),
                    new Minute(0)
                ),
                new DayOfWeekCollection(
                    DayOfWeek::TUESDAY(),
                    DayOfWeek::WEDNESDAY(),
                    DayOfWeek::THURSDAY(),
                    DayOfWeek::FRIDAY()
                )
            ),
            new OpeningHour(
                new OpeningTime(
                    new Hour(9),
                    new Minute(0)
                ),
                new OpeningTime(
                    new Hour(12),
                    new Minute(0)
                ),
                new DayOfWeekCollection(
                    DayOfWeek::SATURDAY()
                )
            ),
        ];

        return new Calendar(
            CalendarType::PERMANENT(),
            null,
            null,
            [],
            $openingHours
        );
    }

    /**
     * @test
     */
    public function it_should_updated_the_workflow_status_when_an_event_is_published()
    {
        $eventId = $this->getEventId();
        $now = new \DateTime('2016-10-26T11:01:57');

        $test = $this->given(OfferType::EVENT())
            ->apply(new EventPublished($eventId, $now))
            ->expect('event-with-workflow-status-published.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_updated_the_workflow_status_when_an_event_is_approved()
    {
        $eventId = $this->getEventId();

        $test = $this->given(OfferType::EVENT())
            ->apply(new EventApproved($eventId))
            ->expect('event-with-workflow-status-approved.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_updated_the_workflow_status_when_a_place_is_approved()
    {
        $placeId = $this->getPlaceId();

        $test = $this->given(OfferType::PLACE())
            ->apply(new PlaceApproved($placeId))
            ->expect('actor-place-with-workflow-status-approved.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider rejectionEventsDataProvider
     * @param OfferType $offerType
     * @param AbstractEvent $event
     * @param string $expectedDocument
     */
    public function it_should_updated_the_workflow_status_when_an_offer_is_rejected(
        OfferType $offerType,
        AbstractEvent $event,
        $expectedDocument
    ) {

        $test = $this->given($offerType)
            ->apply($event)
            ->expect($expectedDocument);

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_project_a_calendar_summary_when_projecting_an_event_with_opening_hours()
    {
        $this->createEvent(false);
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $weekDays = new DayOfWeekCollection(
            DayOfWeek::MONDAY(),
            DayOfWeek::TUESDAY(),
            DayOfWeek::WEDNESDAY(),
            DayOfWeek::THURSDAY(),
            DayOfWeek::FRIDAY()
        );

        $weekendDays = new DayOfWeekCollection(
            DayOfWeek::SATURDAY(),
            DayOfWeek::SUNDAY()
        );

        // add the major info to the event.
        $majorInfoUpdated = new MajorInfoUpdated(
            $id,
            new Title("Nieuwe titel"),
            new EventType("0.50.4.0.0", "concert"),
            new Location(
                $this->getPlaceId(),
                new StringLiteral('Bibberburcht'),
                new Address(
                    new Street('Bondgenotenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            ),
            new Calendar(
                CalendarType::PERMANENT(),
                null,
                null,
                [],
                [
                    new OpeningHour(
                        OpeningTime::fromNativeString('10:00'),
                        OpeningTime::fromNativeString('19:00'),
                        $weekDays
                    ),
                    new OpeningHour(
                        OpeningTime::fromNativeString('12:00'),
                        OpeningTime::fromNativeString('19:00'),
                        $weekendDays
                    ),
                ]
            ),
            new Theme('1.8.2.0.0', 'Jazz en blues')
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $majorInfoUpdated,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-opening-hours.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_make_an_event_private_when_audience_type_is_set_to_members()
    {
        $audienceUpdatedEvent = new AudienceUpdated(
            $this->getEventId(),
            new Audience(AudienceType::MEMBERS())
        );

        $test = $this
            ->given(OfferType::EVENT())
            ->apply($audienceUpdatedEvent)
            ->expect('event-private.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_make_an_event_private_when_audience_type_is_set_to_education()
    {
        $audienceUpdatedEvent = new AudienceUpdated(
            $this->getEventId(),
            new Audience(AudienceType::EDUCATION())
        );

        $test = $this
            ->given(OfferType::EVENT())
            ->apply($audienceUpdatedEvent)
            ->expect('event-with-education-target-audience.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_add_target_audience_category_Scholen_when_an_event_audience_type_is_set_to_education()
    {
        $audienceUpdatedEvent = new AudienceUpdated(
            $this->getEventId(),
            new Audience(AudienceType::EDUCATION())
        );

        $test = $this
            ->given(OfferType::EVENT())
            ->apply($audienceUpdatedEvent)
            ->expect('event-with-education-target-audience.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_replace_the_existing_type_when_updating_with_a_new_type()
    {
        $typeUpdatedEvent = new TypeUpdated(
            $this->getEventId(),
            new EventType('YVBc8KVdrU6XfTNvhMYUpg', 'Discotheek')
        );

        $test = $this
            ->given(OfferType::EVENT())
            ->apply($typeUpdatedEvent)
            ->expect('event-with-type-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_should_replace_the_existing_theme_when_updating_with_a_new_theme()
    {
        $themeUpdatedEvent = new ThemeUpdated(
            $this->getEventId(),
            new Theme('1.8.3.3.0', 'Dance')
        );

        $test = $this
            ->given(OfferType::EVENT())
            ->apply($themeUpdatedEvent)
            ->expect('event-with-theme-updated.xml');

        $this->execute($test);
    }

    /**
     * @test
     * @dataProvider switchingAudienceTypeDataProvider
     * @param AudienceUpdated $fromAudienceUpdated
     * @param AudienceUpdated $toAudienceUpdated
     * @param string $result
     */
    public function it_should_switch_between_audience_types(
        AudienceUpdated $fromAudienceUpdated,
        AudienceUpdated $toAudienceUpdated,
        $result
    ) {
        $test = $this
            ->given(OfferType::EVENT())
            ->apply($fromAudienceUpdated)
            ->apply($toAudienceUpdated)
            ->expect($result);

        $this->execute($test);
    }

    /**
     * @return array
     */
    public function switchingAudienceTypeDataProvider()
    {
        return [
            "update event from audienceType 'everyone' to audienceType 'members'" => [
                'from' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EVERYONE())),
                'to' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::MEMBERS())),
                'result' => 'event-private.xml',
            ],
            "update event from audienceType 'everyone' to audienceType 'education'" => [
                'from' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EVERYONE())),
                'to' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EDUCATION())),
                'result' => 'event-with-education-target-audience.xml',
            ],
            "update event from audienceType 'members' to audienceType 'everyone'" => [
                'from' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::MEMBERS())),
                'to' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EVERYONE())),
                'result' => 'event.xml',
            ],
            "update event from audienceType 'members' to audienceType 'education'" => [
                'from' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::MEMBERS())),
                'to' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EDUCATION())),
                'result' => 'event-with-education-target-audience.xml',
            ],
            "update event from audienceType 'education' to audienceType 'everyone'" => [
                'from' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EDUCATION())),
                'to' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EVERYONE())),
                'result' => 'event.xml',
            ],
            "update event from audienceType 'education' to audienceType 'members'" => [
                'from' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::EDUCATION())),
                'to' => new AudienceUpdated($this->getEventId(), new Audience(AudienceType::MEMBERS())),
                'result' => 'event-private.xml',
            ],
        ];
    }

    /**
     * @return array
     */
    public function rejectionEventsDataProvider()
    {
        return [
            'event rejected' => [
                'offerType' => OfferType::EVENT(),
                'event' => new EventRejected(
                    $this->getEventId(),
                    new StringLiteral('Image contains nudity.')
                ),
                'expectedDocument' => 'event-with-workflow-status-rejected.xml',
            ],
            'event flagged as duplicate' => [
                'offerType' => OfferType::EVENT(),
                'event' => new EventFlaggedAsDuplicate($this->getEventId()),
                'expectedDocument' => 'event-with-workflow-status-rejected.xml',
            ],
            'event flagged as inappropriate' => [
                'offerType' => OfferType::EVENT(),
                'event' => new EventFlaggedAsInappropriate($this->getEventId()),
                'expectedDocument' => 'event-with-workflow-status-rejected.xml',
            ],
            'place rejected' => [
                'offerType' => OfferType::PLACE(),
                'event' => new PlaceRejected(
                    $this->getPlaceId(),
                    new StringLiteral('Image contains nudity.')
                ),
                'expectedDocument' => 'actor-place-with-workflow-status-rejected.xml',
            ],
            'place flagged as duplicate' => [
                'offerType' => OfferType::PLACE(),
                'event' => new PlaceFlaggedAsDuplicate($this->getPlaceId()),
                'expectedDocument' => 'actor-place-with-workflow-status-rejected.xml',
            ],
            'place flagged as inappropriate' => [
                'offerType' => OfferType::PLACE(),
                'event' => new PlaceFlaggedAsInappropriate($this->getPlaceId()),
                'expectedDocument' => 'actor-place-with-workflow-status-rejected.xml',
            ],
        ];
    }

    /**
     * @return array
     */
    public function genericOfferTestDataProvider()
    {
        return [
            [
                OfferType::EVENT(),
                $this->getEventId(),
                'event',
            ],
            [
                OfferType::PLACE(),
                $this->getPlaceId(),
                'actor-place',
            ],
        ];
    }

    /**
     * @param OfferType $offerType
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    private function given(OfferType $offerType)
    {
        return (new OfferToCdbXmlProjectorTestBuilder($this->getDefaultMetadata()))
            ->given($offerType);
    }

    /**
     * @param OfferToCdbXmlProjectorTestBuilder $test
     */
    private function execute(OfferToCdbXmlProjectorTestBuilder $test)
    {
        $id = $this->createOffer($test->getOfferType());

        $stream = $this->createDomainEventStream(
            $id,
            $test->getEvents(),
            $test->getMetadata()
        );

        $expectedCdbXmlDocuments = [];

        foreach ($test->getExpectedCdbXmlFiles() as $expectedCdbXmlFile) {
            $expectedCdbXmlDocuments[] = new CdbXmlDocument(
                $id,
                $this->loadCdbXmlFromFile($expectedCdbXmlFile)
            );
        }

        $this->logger->expects($this->any())
            ->method('error')
            ->willReturnCallback(
                function ($message, $context) {
                    $this->fail($message . ' (' . $context['message'] . ')');
                }
            );

        $this->handleDomainEventStream($stream);

        $this->assertFinalCdbXmlDocumentInRepository($expectedCdbXmlDocuments);
    }

    /**
     * @param OfferType $offerType
     * @return string
     * @uses createPlace, createEvent
     */
    private function createOffer(OfferType $offerType)
    {
        $method = 'create' . $offerType->toNative();

        if (!method_exists($this, $method)) {
            throw new \RuntimeException('Could not create offer of type ' . $offerType->toNative());
        }

        return $this->{$method}();
    }

    /**
     * @return string
     */
    private function getEventId()
    {
        return '404EE8DE-E828-9C07-FE7D12DC4EB24480';
    }

    /**
     * Helper function to create an event.
     *
     * @param bool $theme
     *   Whether or not to add a theme to the event
     *
     * @return string
     */
    private function createEvent($theme = true)
    {
        $id = $this->getEventId();
        $timestamps = $this->getTimestamps();

        $address = new Address(
            new Street('Bondgenotenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $location = new Location(
            $this->getPlaceId(),
            new StringLiteral('Bibberburcht'),
            $address
        );

        $placeCreated = new PlaceCreated(
            $this->getPlaceId(),
            new Language('nl'),
            new Title('Bibberburcht'),
            new EventType('0.50.4.0.0', 'concert'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage($this->getPlaceId(), $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $theme = $theme?new Theme('1.7.6.0.0', 'Griezelfilm of horror'):null;
        $event = new EventCreated(
            $id,
            new Language('nl'),
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            $location,
            new Calendar(
                CalendarType::MULTIPLE(),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                $timestamps
            ),
            $theme
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $this->projector->handle($domainMessage);

        return $id;
    }

    /**
     * @return string
     */
    private function getPlaceId()
    {
        return 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';
    }

    /**
     * Helper function to create a place.
     *
     * @return string
     */
    private function createPlace()
    {
        $id = $this->getPlaceId();

        $place = new PlaceCreated(
            $id,
            new Language('nl'),
            new Title('My Place'),
            new EventType('0.50.4.0.0', 'concert'),
            $address = new Address(
                new Street('Bondgenotenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $domainMessage = $this->createDomainMessage($id, $place, $this->metadata);

        $this->projector->handle($domainMessage);

        return $id;
    }

    /**
     * @return string
     */
    private function getKerkPlaceId()
    {
        return 'ece91dd1-07cc-45c7-bfb6-576847d4e836';
    }

    /**
     * Helper function to create a Kerkstraat place.
     *
     * @return string
     */
    private function createKerkPlace()
    {
        $id = $this->getKerkPlaceId();

        $place = new PlaceCreated(
            $id,
            new Language('nl'),
            new Title('Kerk'),
            new EventType('8.4.0.0.0', 'Galerie'),
            $address = new Address(
                new Street('Kerkstraat 69'),
                new PostalCode('1000'),
                new Locality('Brussel'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.0.1.0.0', 'Schilderkunst')
        );

        $domainMessage = $this->createDomainMessage($id, $place, $this->metadata);

        $this->projector->handle($domainMessage);

        return $id;
    }

    /**
     * @return Metadata
     */
    private function getDefaultMetadata()
    {
        return new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );
    }

    /**
     * @return Timestamp[]
     */
    private function getTimestamps()
    {
        return [
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T12:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T15:00:00+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T12:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T15:00:00+01:00')
            ),
        ];
    }
}
