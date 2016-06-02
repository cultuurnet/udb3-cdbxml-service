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
use CultuurNet\UDB3\Offer\Offer;
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
     */
    public function it_projects_translation_applied_to_events()
    {
        $id = $this->createEvent();

        $events = [
            new TranslationApplied(
                new StringLiteral($id),
                new Language('en'),
                new StringLiteral('Horror movie'),
                new StringLiteral('This is a short description.'),
                new StringLiteral('This is a long, long, long, very long description.')
            ),
            new TranslationApplied(
                new StringLiteral($id),
                new Language('en'),
                new StringLiteral('Horror movie updated'),
                new StringLiteral('This is a short description updated.'),
                new StringLiteral('This is a long, long, long, very long description updated.')
            ),
        ];

        $expectedCdbXmlDocuments = [
            new CdbXmlDocument(
                $id,
                $this->loadCdbXmlFromFile('event-with-translation-applied-en-added.xml')
            ),
            new CdbXmlDocument(
                $id,
                $this->loadCdbXmlFromFile('event-with-translation-applied-en-updated.xml')
            ),
        ];

        $stream = $this->createDomainEventStream($id, $events, $this->metadata);

        $this->handleDomainEventStream($stream);

        $this->assertCdbXmlDocumentsArePublished($expectedCdbXmlDocuments);
        $this->assertFinalCdbXmlDocumentInRepository($expectedCdbXmlDocuments);
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
    public function it_projects_the_deletion_of_a_translation()
    {
        $id = $this->createEvent();

        $events = [
            new TranslationApplied(
                new StringLiteral($id),
                new Language('en'),
                new StringLiteral('Horror movie'),
                new StringLiteral('This is a short description.'),
                new StringLiteral('This is a long, long, long, very long description.')
            ),
            new TranslationApplied(
                new StringLiteral($id),
                new Language('fr'),
                new StringLiteral('Filme d\'horreur'),
                new StringLiteral('Description courte'),
                new StringLiteral('Une description qui est assez longue......')
            ),
            new TranslationDeleted(
                new StringLiteral($id),
                new Language('en')
            ),
        ];

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-en-translation-removed.xml')
        );

        $stream = $this->createDomainEventStream($id, $events, $this->metadata);
        $this->handleDomainEventStream($stream);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     * @dataProvider genericOfferEventDataProvider
     *
     * @param OfferType $type
     * @param array $events
     * @param Metadata $metadata
     * @param string[] $expectedCdbXmlFiles
     */
    public function it_projects_generic_offer_events(
        OfferType $type,
        array $events,
        Metadata $metadata,
        array $expectedCdbXmlFiles
    ) {
        $id = $this->createOffer($type);

        $stream = $this->createDomainEventStream($id, $events, $metadata);

        $expectedCdbXmlDocuments = [];

        foreach ($expectedCdbXmlFiles as $expectedCdbXmlFile) {
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
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    protected function given(OfferType $offerType)
    {
        return (new OfferToCdbXmlProjectorTestBuilder($this->getDefaultMetadata()))
            ->given($offerType);
    }

    /**
     * @return array
     */
    public function genericOfferEventDataProvider()
    {
        return [
            // Event TitleTranslated
            $this->given(OfferType::EVENT())
                ->apply(
                    new TitleTranslated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Language('en'),
                        new StringLiteral('Horror movie')
                    )
                )
                ->expect('event-with-title-translated-to-en.xml')
                ->apply(
                    new TitleTranslated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Language('en'),
                        new StringLiteral('Horror movie updated')
                    )
                )
                ->expect('event-with-title-translated-to-en-updated.xml')
                ->finish(),

            // Place TitleTranslated
            $this->given(OfferType::PLACE())
                ->apply(
                    new TitleTranslated(
                        'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6',
                        new Language('en'),
                        new StringLiteral('Horror movie')
                    )
                )
                ->expect('actor-with-title-translated-to-en.xml')
                ->apply(
                    new TitleTranslated(
                        'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6',
                        new Language('en'),
                        new StringLiteral('Horror movie updated')
                    )
                )
                ->expect('actor-with-title-translated-to-en-updated.xml')
                ->finish(),

            // Event DescriptionTranslated
            $this->given(OfferType::EVENT())
                ->apply(
                    new DescriptionTranslated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Language('en'),
                        new StringLiteral('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.')
                    )
                )
                ->expect('event-with-description-translated-to-en.xml')
                ->apply(
                    new DescriptionTranslated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Language('en'),
                        new StringLiteral('Description updated.')
                    )
                )
                ->expect('event-with-description-translated-to-en-updated.xml')
                ->finish(),

            // Event DescriptionUpdated
            $this->given(OfferType::EVENT())
                ->apply(
                    new DescriptionUpdated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
                    )
                )
                ->expect('event-with-description.xml')
                ->finish(),

            // Event LabelAdded, LabelDeleted
            $this->given(OfferType::EVENT())
                ->apply(
                    new LabelAdded(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Label('foobar')
                    )
                )
                ->expect('event-with-keyword.xml')
                ->apply(
                    new LabelAdded(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Label('foobar', false)
                    )
                )
                ->expect('event-with-keyword-visible-false.xml')
                ->apply(
                    new LabelDeleted(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                        new Label('foobar')
                    )
                )
                ->expect('event.xml')
                ->finish(),

            // Event BookingInfoUpdated
            $this->given(OfferType::EVENT())
                ->apply(
                    new BookingInfoUpdated(
                        '404EE8DE-E828-9C07-FE7D12DC4EB24480',
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
                ->expect('event-booking-info-updated.xml')
                ->finish(),
        ];
    }

    /**
     * @test
     */
    public function it_projects_the_update_of_a_contact_point()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $contactPoint = new ContactPoint(
            array('+32 666 666'),
            array('tickets@example.com'),
            array('http://tickets.example.com'),
            'type'
        );

        $contactPointUpdated = new ContactPointUpdated(
            $id,
            $contactPoint
        );

        $domainMessage = $this->createDomainMessage($id, $contactPointUpdated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-contact-point-updated.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     *
     */
    public function it_projects_the_update_of_a_description()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461155055',
            ]
        );

        $description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

        $descriptionUpdated = new DescriptionUpdated(
            $id,
            $description
        );

        $domainMessage = $this->createDomainMessage($id, $descriptionUpdated, $metadata);
        $this->projector->handle($domainMessage);

        $description = 'Description updated';

        $descriptionUpdated = new DescriptionUpdated(
            $id,
            $description
        );

        $domainMessage = $this->createDomainMessage($id, $descriptionUpdated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-description-updated.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_does_not_add_an_existing_label_when_projecting_label_added()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // Add the label once.
        $labelAdded = new LabelAdded($id, new Label('foobar'));

        $domainMessage = $this->createDomainMessage($id, $labelAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        // Add the label again.
        $labelAdded = new LabelAdded($id, new Label('foobar'));
        $domainMessage = $this->createDomainMessage($id, $labelAdded, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-keyword.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_does_not_do_a_thing_when_deleting_a_label_twice()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // First add a label.
        $labelAdded = new LabelAdded($id, new Label('foobar'));
        $domainMessage = $this->createDomainMessage($id, $labelAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        // Now delete the label.
        $labelDeleted = new LabelDeleted($id, new Label('foobar'));
        $domainMessage = $this->createDomainMessage($id, $labelDeleted, $this->metadata);
        $this->projector->handle($domainMessage);

        // Now delete the label again.
        $labelDeleted = new LabelDeleted($id, new Label('foobar'));
        $domainMessage = $this->createDomainMessage($id, $labelDeleted, $this->metadata);

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
    public function it_adds_a_media_file_when_adding_an_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-image.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_updates_the_event_media_object_property_when_updating_an_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $imageUpdated = new ImageUpdated(
            $id,
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new StringLiteral('Sexy ladies without clothes - NSFW'),
            new StringLiteral('John Doe')
        );

        $domainMessage = $this->createDomainMessage($id, $imageUpdated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-image-updated.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_deletes_a_media_file_when_removing_an_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $imageRemoved = new ImageRemoved(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageRemoved, $this->metadata);

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
    public function it_should_make_the_oldest_image_main_when_deleting_the_current_main_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $olderImage = new Image(
            new UUID('9554d6f6-bed1-4303-8d42-3fcec4601e0e'),
            new MIMEType('image/jpg'),
            new StringLiteral('Beep Boop'),
            new StringLiteral('Noo Idee'),
            Url::fromNative('http://foo.bar/media/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg')
        );

        $imageAdded = new ImageAdded(
            $id,
            $olderImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $newImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $newImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $imageRemoved = new ImageRemoved(
            $id,
            $olderImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageRemoved, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-image.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_update_the_image_property_when_selecting_a_main_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $newImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('John Doe'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $newImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $image = new Image(
            new UUID('9554d6f6-bed1-4303-8d42-3fcec4601e0e'),
            new MIMEType('image/jpg'),
            new StringLiteral('Beep Boop'),
            new StringLiteral('Noo Idee'),
            Url::fromNative('http://foo.bar/media/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        // Now change the main image.
        $mainImageSelected = new MainImageSelected(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $mainImageSelected, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-images.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_deletion_of_an_event()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $eventDeleted = new EventDeleted(
            $id
        );

        $domainMessage = $this->createDomainMessage($id, $eventDeleted, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-deleted.xml')
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
        $this->createPlace();
        $id = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

        $placeDeleted = new PlaceDeleted(
            $id
        );

        $domainMessage = $this->createDomainMessage($id, $placeDeleted, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-deleted.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_an_organizer_updated()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // create an organizer.
        $organizerId = 'ORG-123';
        $organizerCdbxml = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->actorRepository->save($organizerCdbxml);

        // add the organizer to the event.
        $organizerUpdated = new OrganizerUpdated($id, $organizerId);
        $domainMessage = $this->createDomainMessage($id, $organizerUpdated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-organizer.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_logs_a_warning_when_organizer_updated_but_organizer_not_found()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // create an organizer.
        $organizerId = 'ORG-123';

        // add the organizer to the event.
        $organizerUpdated = new OrganizerUpdated($id, $organizerId);
        $domainMessage = $this->createDomainMessage($id, $organizerUpdated, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-without-organizer.xml')
        );

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find organizer with id ORG-123 when applying organizer updated on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_an_organizer_deleted()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // create an organizer.
        $organizerId = 'ORG-123';
        $organizerCdbxml = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->actorRepository->save($organizerCdbxml);

        // add the organizer to the event.
        $organizerUpdated = new OrganizerUpdated($id, $organizerId);
        $domainMessage = $this->createDomainMessage($id, $organizerUpdated, $this->metadata);
        $this->projector->handle($domainMessage);

        // remove the organizer from the event.
        $organizerDeleted = new OrganizerDeleted($id, $organizerId);
        $domainMessage = $this->createDomainMessage($id, $organizerDeleted, $this->metadata);

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
    public function it_should_project_an_updated_list_of_categories_when_place_facilities_have_changed()
    {
        $this->createPlace();
        $placeId = '061C13AC-A15F-F419-D8993D68C9E94548';

        $originalPlaceCdbXml = new CdbXmlDocument(
            $placeId,
            $this->loadCdbXmlFromFile('place-with-facilities.xml')
        );
        $this->actorRepository->save($originalPlaceCdbXml);

        $facilities = [
            new Facility('3.13.2.0.0', 'Audiodescriptie'),
            new Facility('3.17.3.0.0', 'Ondertiteling'),
            new Facility('3.17.1.0.0', 'Ringleiding'),
        ];
        $facilitiesUpdates = new FacilitiesUpdated($placeId, $facilities);
        $domainMessage = $this->createDomainMessage($placeId, $facilitiesUpdates, $this->metadata);
        $this->projector->handle($domainMessage);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $placeId,
            $this->loadCdbXmlFromFile('place-with-updated-facilities.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentIsPublished($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
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
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        // add some random changes
        $typicalAgeRangeUpdated = new TypicalAgeRangeUpdated($id, "9-12");
        $domainMessage = $this->createDomainMessage($id, $typicalAgeRangeUpdated, $this->metadata);
        $this->projector->handle($domainMessage);

        // update from udb2 event
        $eventUpdatedFromUdb2 = new EventUpdatedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('event-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );
        $domainMessage = $this->createDomainMessage(
            $id,
            $eventUpdatedFromUdb2,
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
     * Helper function to create an event.
     *
     * @param bool $theme
     *   Whether or not to add a theme to the event
     *
     * @return string
     */
    private function createEvent($theme = true)
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

        $placeId = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

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
     * Helper function to create a place.
     *
     * @return string
     */
    private function createPlace()
    {
        $id = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';

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
