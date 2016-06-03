<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
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
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
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
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2Event;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use Psr\Log\LoggerInterface;
use ValueObjects\Identity\UUID;
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

        $this->projector = (
        new OfferToCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            new MetadataCdbItemEnricher(
                new CdbXmlDateFormatter()
            ),
            $this->actorRepository,
            new CdbXmlDateFormatter(),
            new AddressFactory()
        )
        )->withCdbXmlPublisher($this->cdbXmlPublisher);

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
        $placeId = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

        $placeCreated = new PlaceCreated(
            $placeId,
            new Title('$name'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address('$street', '$postalCode', '$locality', '$country'),
            new Calendar('permanent')
        );
        $domainMessage = $this->createDomainMessage($id, $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $domainMessage = $this->createDomainMessage($id, $eventCreated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile($cdbXmlFileName)
        );

        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function eventCreatedDataProvider()
    {
        $timestamps = [
            new Timestamp(
                '2014-01-31T12:00:00',
                '2014-01-31T15:00:00'
            ),
            new Timestamp(
                '2014-02-20T12:00:00',
                '2014-02-20T15:00:00'
            ),
        ];

        return [
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                new EventCreated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Title('Griezelfilm of horror'),
                    new EventType('0.50.6.0.0', 'film'),
                    new Location('C4ACF936-1D5F-48E8-B2EC-863B313CBDE6', '$name', '$country', '$locality', '$postalcode', '$street'),
                    new Calendar('multiple', '2014-01-31T13:00:00+01:00', '2014-02-20T16:00:00+01:00', $timestamps),
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
                    new Location('C4ACF936-1D5F-48E8-B2EC-863B313CBDE6', '$name', '$country', '$locality', '$postalcode', '$street'),
                    new Calendar('multiple', '2014-01-31T13:00:00+01:00', '2014-02-20T16:00:00+01:00', $timestamps),
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

        $timestamps = [
            new Timestamp(
                '2014-01-31T12:00:00',
                '2014-01-31T15:00:00'
            ),
            new Timestamp(
                '2014-02-20T12:00:00',
                '2014-02-20T15:00:00'
            ),
        ];

        $placeId = 'LOCATION-MISSING';

        $placeCreated = new PlaceCreated(
            $placeId,
            new Title('$name'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address('$street', '$postalCode', '$locality', '$country'),
            new Calendar('permanent')
        );
        $domainMessage = $this->createDomainMessage($id, $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $event = new EventCreated(
            $id,
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            new Location('34973B89-BDA3-4A79-96C7-78ACC022907D', '$name', '$country', '$locality', '$postalcode', '$street'),
            new Calendar('multiple', '2014-01-31T13:00:00+01:00', '2014-02-20T16:00:00+01:00', $timestamps),
            new Theme('1.7.6.0.0', 'Griezelfilm of horror')
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-without-location.xml')
        );

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find location with id 34973B89-BDA3-4A79-96C7-78ACC022907D when setting location on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function placeCreatedDataProvider()
    {
        return [
            [
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                new PlaceCreated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Title('My Place'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Address('$street', '$postalCode', '$locality', '$country'),
                    new Calendar('permanent')
                ),
                'place.xml',
            ],
            [
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                new PlaceCreated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Title('My Place'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new Address('$street', '$postalCode', '$locality', '$country'),
                    new Calendar('permanent'),
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
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
     */
    public function it_projects_description_translations()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

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
     */
    public function it_projects_description_updates()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

        $test = $this->given($offerType)
            ->apply(
                new DescriptionUpdated(
                    $id,
                    'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
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
     */
    public function it_projects_label_events()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

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
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Label('foobar')
                )
            )
            ->expect($cdbXmlType . '.xml');

        $this->execute($test);
    }

    /**
     * @test
     */
    public function it_projects_booking_info_events()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

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
    public function it_projects_contact_point_events()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

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
     */
    public function it_projects_basic_image_events()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

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
     */
    public function it_projects_main_image_selected_events()
    {
        $offerType = OfferType::EVENT();
        $id = $this->getEventId();
        $cdbXmlType = 'event';

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
    public function it_should_add_keywords_to_the_projection_when_labels_are_merged()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $originalPlaceCdbXml = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-keyword.xml')
        );
        $this->actorRepository->save($originalPlaceCdbXml);

        $mergedLabels = new LabelCollection(
            [
                new Label('foob'),
                // foobar is already added to the document but we add it to make sure we don't end up with doubles.
                new Label('foobar'),
                new Label('barb', false),
            ]
        );
        $labelsMerged = new LabelsMerged(StringLiteral::fromNative($id), $mergedLabels);
        $domainMessage = $this->createDomainMessage($id, $labelsMerged, $this->metadata);
        $this->projector->handle($domainMessage);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-merged-labels-as-keywords.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_typical_age_range_updated()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // add the typical age range to the event.
        $typicalAgeRangeUpdated = new TypicalAgeRangeUpdated($id, "9-12");
        $domainMessage = $this->createDomainMessage($id, $typicalAgeRangeUpdated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-age-from.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_typical_age_range_deleted()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // add the typical age range to the event.
        $typicalAgeRangeUpdated = new TypicalAgeRangeUpdated($id, "9-12");
        $domainMessage = $this->createDomainMessage($id, $typicalAgeRangeUpdated, $this->metadata);
        $this->projector->handle($domainMessage);

        // remove the typical age range from the event.
        $typicalAgeRangeDeleted = new TypicalAgeRangeDeleted($id);
        $domainMessage = $this->createDomainMessage($id, $typicalAgeRangeDeleted, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_event_major_info_updated()
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
                '$name2',
                '$country',
                '$locality',
                '$postalcode',
                '$street'
            ),
            new Calendar('permanent'),
            new Theme('tid', 'tlabel')
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_logs_a_warning_when_major_info_updated_without_location()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $placeId = 'LOCATION-MISSING';

        // add the major info to the event.
        $majorInfoUpdated = new MajorInfoUpdated(
            $id,
            new Title("Nieuwe titel"),
            new EventType("id", "label"),
            new Location(
                $placeId,
                '$name2',
                '$country',
                '$locality',
                '$postalcode',
                '$street'
            ),
            new Calendar('permanent'),
            new Theme('tid', 'tlabel')
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $majorInfoUpdated,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-major-info-updated-without-location.xml')
        );

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find location with id LOCATION-MISSING when setting location on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_event_without_theme_major_info_updated()
    {
        $this->createEvent(false);
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $placeId = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

        // add the major info to the event.
        $majorInfoUpdated = new MajorInfoUpdated(
            $id,
            new Title("Nieuwe titel"),
            new EventType("id", "label"),
            new Location(
                $placeId,
                '$name2',
                '$country',
                '$locality',
                '$postalcode',
                '$street'
            ),
            new Calendar('permanent'),
            new Theme('tid', 'tlabel')
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
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
                '$name2',
                '$country',
                '$locality',
                '$postalcode',
                '$street'
            ),
            new Calendar('permanent')
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

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_place_major_info_updated()
    {
        $this->createPlace();
        $id = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

        // add the major info to the event.
        $majorInfoUpdated = new PlaceMajorInfoUpdated(
            $id,
            new Title("Nieuwe titel"),
            new EventType("id", "label"),
            new Address(
                '$street2',
                '$postalCode2',
                '$locality2',
                '$country2'
            ),
            new Calendar('permanent'),
            new Theme('tid', 'tlabel')
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $majorInfoUpdated,
            $this->metadata
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-with-major-info-updated.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_event_collaboration_data_added()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // add collaboration data
        $dataArray = [
            'copyright' => 'Kristof Coomans',
            'text' => "this is the text 2",
            'keyword' => "foo",
            'article' => "bar",
            'plainText' => 'whatever',
            'title' =>  'title',
            'subBrand' => 'e36c2db19aeb6d2760ce0500d393e83c',
        ];
        $collaborationData = CollaborationData::deserialize($dataArray);
        $collaborationDataAdded = new CollaborationDataAdded(
            String::fromNative($id),
            new Language("nl"),
            $collaborationData
        );
        $domainMessage = $this->createDomainMessage($id, $collaborationDataAdded, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-collaboration-data.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
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
                    new StringLiteral('Horror movie updated'),
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
                'actor',
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

        $this->handleDomainEventStream($stream);

        $this->assertCdbXmlDocumentsArePublished($expectedCdbXmlDocuments);
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

        $timestamps = [
            new Timestamp(
                '2014-01-31T12:00:00',
                '2014-01-31T15:00:00'
            ),
            new Timestamp(
                '2014-02-20T12:00:00',
                '2014-02-20T15:00:00'
            ),
        ];

        $placeId = $this->getPlaceId();

        $placeCreated = new PlaceCreated(
            $placeId,
            new Title('$name'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address('$street', '$postalCode', '$locality', '$country'),
            new Calendar('permanent')
        );
        $domainMessage = $this->createDomainMessage($placeId, $placeCreated, $this->metadata);
        $this->projector->handle($domainMessage);

        $theme = $theme?new Theme('1.7.6.0.0', 'Griezelfilm of horror'):null;
        $event = new EventCreated(
            $id,
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            new Location('C4ACF936-1D5F-48E8-B2EC-863B313CBDE6', '$name', '$country', '$locality', '$postalcode', '$street'),
            new Calendar('multiple', '2014-01-31T13:00:00+01:00', '2014-02-20T16:00:00+01:00', $timestamps),
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
            new Address('$street', '$postalCode', '$locality', '$country'),
            new Calendar('permanent')
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
}
