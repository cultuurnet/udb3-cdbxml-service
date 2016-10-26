<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CollaborationData;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CollaborationDataAdded;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelDeleted;
use CultuurNet\UDB3\Event\Events\LabelsMerged;
use CultuurNet\UDB3\Event\Events\MainImageSelected;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Published as EventPublished;
use CultuurNet\UDB3\Event\Events\Moderation\Approved as EventApproved;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected as EventRejected;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate as EventFlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate as EventFlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TranslationApplied;
use CultuurNet\UDB3\Event\Events\TranslationDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Location\Location;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2Event;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\Moderation\Approved as PlaceApproved;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected as PlaceRejected;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate as PlaceFlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate as PlaceFlaggedAsInappropriate;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\NewlineToBreakTagStringFilter;
use CultuurNet\UDB3\StringFilter\NewlineToSpaceStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use Psr\Log\LoggerInterface;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\String\String;
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

        $shortDescriptionFilter = new CombinedStringFilter();
        $shortDescriptionFilter->addFilter(new NewlineToSpaceStringFilter());
        $shortDescriptionFilter->addFilter(new TruncateStringFilter(400));

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
            new NewlineToBreakTagStringFilter(),
            $shortDescriptionFilter,
            new CurrencyRepository(),
            new NumberFormatRepository()
        ));

        $this->logger = $this->getMock(LoggerInterface::class);
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
            new Title('$name'),
            new EventType('0.50.4.0.0', 'concert'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage($id, $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $event = new EventCreated(
            $id,
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
            $this->loadCdbXmlFromFile('event.xml')
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
                    "9-12"
                )
            )
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-namespaced.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )
            ->expect('event.xml');

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
     * @dataProvider placeImportedFromUdb2DataProvider
     * @param ActorImportedFromUDB2 $actorImportedFromUDB2
     * @param string $expectedCdbXmlFile
     */
    public function it_projects_imported_actor_places_from_udb2_as_actors(
        ActorImportedFromUDB2 $actorImportedFromUDB2,
        $expectedCdbXmlFile
    ) {
        $id = $actorImportedFromUDB2->getActorId();

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile($expectedCdbXmlFile)
        );

        $domainMessage = $this->createDomainMessage($id, $actorImportedFromUDB2, $this->metadata);

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function placeImportedFromUdb2DataProvider()
    {
        return [
            [
                new PlaceImportedFromUDB2(
                    '061C13AC-A15F-F419-D8993D68C9E94548',
                    file_get_contents(__DIR__ . '/Repository/samples/place-actor.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                ),
                'place-actor-generated.xml',
            ],
            [
                new PlaceUpdatedFromUDB2(
                    '061C13AC-A15F-F419-D8993D68C9E94548',
                    file_get_contents(__DIR__ . '/Repository/samples/place-actor.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                ),
                'place-actor-generated.xml',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_projects_places_imported_from_udb2_events()
    {
        $id = '34973B89-BDA3-4A79-96C7-78ACC022907D';

        $placeImportedFromUdb2Event = new PlaceImportedFromUDB2Event(
            $id,
            $this->loadCdbXmlFromFile('place-event-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage(
            $id,
            $placeImportedFromUdb2Event,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-event-namespaced-to-actor.xml')
        );

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
                    new StringLiteral('Horror movie')
                )
            )
            ->expect($cdbXmlType . '-with-title-translated-to-en.xml')
            ->apply(
                new TitleTranslated(
                    $id,
                    new Language('en'),
                    new StringLiteral('Horror movie updated')
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
                    new StringLiteral('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.')
                )
            )
            ->expect($cdbXmlType . '-with-description-translated-to-en.xml')
            ->apply(
                new DescriptionTranslated(
                    $id,
                    new Language('en'),
                    new StringLiteral('Description updated.')
                )
            )
            ->expect($cdbXmlType . '-with-description-translated-to-en-updated.xml');

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
    public function it_projects_description_updates(
        OfferType $offerType,
        $id,
        $cdbXmlType
    ) {
        $test = $this->given($offerType)
            ->apply(
                new DescriptionUpdated(
                    $id,
                    "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\nUt enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."
                )
            )
            ->expect($cdbXmlType . '-with-description.xml')
            ->apply(
                new DescriptionUpdated(
                    $id,
                    'Description updated'
                )
            )
            ->expect($cdbXmlType . '-with-description-updated.xml');

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
                new LabelDeleted(
                    $id,
                    new Label('foobar')
                )
            )
            ->expect($cdbXmlType . '.xml')
            ->apply(
                new LabelDeleted(
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
                        '2014-01-31T12:00:00',
                        '2014-02-20T15:00:00',
                        'booking name'
                    )
                )
            )
            ->expect($cdbXmlType . '-booking-info-updated.xml');

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
                    new StringLiteral('Werkloze dodo kwekers'),
                    Price::fromFloat(7.755),
                    Currency::fromNative('EUR')
                )
            )
            ->withExtraTariff(
                new Tariff(
                    new StringLiteral('Seniele senioren'),
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
                        array('http://tickets.example.com'),
                        'type'
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
            new StringLiteral('title'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
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
            new StringLiteral('title'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $secondImage = new Image(
            new UUID('9554d6f6-bed1-4303-8d42-3fcec4601e0e'),
            new MIMEType('image/jpg'),
            new StringLiteral('Beep Boop'),
            new StringLiteral('Noo Idee'),
            Url::fromNative('http://foo.bar/media/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg')
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
            new StringLiteral('title'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
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
            new StringLiteral('My best selfie.'),
            new StringLiteral('Duck Face'),
            Url::fromNative('http://foo.bar/media/c0c96570-3b3c-4d3f-9d82-c26b290e6c12.png')
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
    public function it_should_project_an_updated_list_of_categories_when_place_facilities_have_changed()
    {
        $test = $this->given(OfferType::PLACE())
            ->apply(
                new FacilitiesUpdated(
                    $this->getPlaceId(),
                    [
                        new Facility('3.13.3.0.0', 'Brochure beschikbaar in braille'),
                        new Facility('3.17.3.0.0', 'Ondertiteling'),
                        new Facility('3.17.1.0.0', 'Ringleiding'),
                    ]
                )
            )->expect('place-with-facilities.xml')
            ->apply(
                new FacilitiesUpdated(
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
    public function it_projects_labels_merged_events()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new EventUpdatedFromUDB2(
                    $this->getEventId(),
                    $this->loadCdbXmlFromFile('event-with-keyword.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                )
            )->apply(
                new LabelsMerged(
                    new StringLiteral($this->getEventId()),
                    new LabelCollection(
                        [
                            new Label('foob'),
                            // foobar is already added to the document but we add it to make sure we don't end up with doubles.
                            new Label('foobar'),
                            new Label('barb', false),
                        ]
                    )
                )
            )->expect('event-with-merged-labels-as-keywords.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_a_typical_age_range_events()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TypicalAgeRangeUpdated(
                    $this->getEventId(),
                    "9-12"
                )
            )
            ->expect('event-with-age-from.xml')
            ->apply(
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
    public function it_projects_event_collaboration_data_added()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new CollaborationDataAdded(
                    String::fromNative($this->getEventId()),
                    new Language("nl"),
                    CollaborationData::deserialize(
                        [
                            'copyright' => 'Kristof Coomans',
                            'text' => "this is the text 2",
                            'keyword' => "foo",
                            'article' => "bar",
                            'plainText' => 'whatever',
                            'title' =>  'title',
                            'subBrand' => 'e36c2db19aeb6d2760ce0500d393e83c',
                        ]
                    )
                )
            )
            ->expect('event-with-collaboration-data.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_old_event_translations()
    {
        $test = $this->given(OfferType::EVENT())
            ->apply(
                new TranslationApplied(
                    new StringLiteral($this->getEventId()),
                    new Language('en'),
                    new StringLiteral('Horror movie'),
                    new StringLiteral('This is a short description.'),
                    new StringLiteral('This is a long, long, long, very long description.')
                )
            )
            ->expect('event-with-translation-applied-en-added.xml')
            ->apply(
                new TranslationApplied(
                    new StringLiteral($this->getEventId()),
                    new Language('en'),
                    new StringLiteral('Horror film'),
                    new StringLiteral('This is a short description updated.'),
                    new StringLiteral('This is a long, long, long, very long description updated.')
                )
            )
            ->expect('event-with-translation-applied-en-updated.xml')
            ->apply(
                new TranslationDeleted(
                    new StringLiteral($this->getEventId()),
                    new Language('en')
                )
            )
            ->expect('event.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_translation_applied_on_missing_document()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480_MISSING';

        $translationApplied = new TranslationApplied(
            new StringLiteral($id),
            new Language('en'),
            new StringLiteral('Horror movie'),
            new StringLiteral('This is a short description.'),
            new StringLiteral('This is a long, long, long, very long description.')
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $this->metadata);

        $message = 'Handle error for uuid=404EE8DE-E828-9C07-FE7D12DC4EB24480_MISSING for type CultuurNet.UDB3.Event.Events.TranslationApplied recorded on ';
        $message .= $domainMessage->getRecordedOn()->toString();

        $this->logger->expects($this->once())->method('error')
            ->with($message);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_updated_the_workflow_status_when_an_event_is_published()
    {
        $eventId = $this->getEventId();
        $now = new \DateTime();

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
            new Title('$name'),
            new EventType('0.50.4.0.0', 'concert'),
            $address,
            new Calendar(CalendarType::PERMANENT())
        );
        $domainMessage = $this->createDomainMessage($this->getPlaceId(), $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $theme = $theme?new Theme('1.7.6.0.0', 'Griezelfilm of horror'):null;
        $event = new EventCreated(
            $id,
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
