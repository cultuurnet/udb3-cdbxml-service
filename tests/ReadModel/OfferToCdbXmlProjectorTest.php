<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\Media\EditImageTestTrait;
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
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelDeleted;
use CultuurNet\UDB3\Event\Events\LabelsMerged;
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
use ValueObjects\String\String as StringLiteral;
use ValueObjects\String\String;

class OfferToCdbXmlProjectorTest extends CdbXmlProjectorTestBase
{
    use EditImageTestTrait;

    /**
     * @var OfferToCdbXmlProjector
     */
    private $projector;

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

        $this->metadata = new Metadata(
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find location with id 34973B89-BDA3-4A79-96C7-78ACC022907D when setting location on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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
    public function it_projects_the_addition_of_a_translation_applied()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventId = new StringLiteral($id);
        $language = new Language('en');
        $title = new StringLiteral('Horror movie');
        $longDescription = new StringLiteral('This is a long, long, long, very long description.');
        $shortDescription = new StringLiteral('This is a short description.');

        $translationApplied = new TranslationApplied(
            $eventId,
            $language,
            $title,
            $shortDescription,
            $longDescription
        );

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-translation-applied-en-added.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_translation_applied_on_missing_document()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480_MISSING';
        $eventId = new StringLiteral($id);
        $language = new Language('en');
        $title = new StringLiteral('Horror movie');
        $longDescription = new StringLiteral('This is a long, long, long, very long description.');
        $shortDescription = new StringLiteral('This is a short description.');

        $translationApplied = new TranslationApplied(
            $eventId,
            $language,
            $title,
            $shortDescription,
            $longDescription
        );

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $metadata);

        $message = 'Handle error for uuid=404EE8DE-E828-9C07-FE7D12DC4EB24480_MISSING for type CultuurNet.UDB3.Event.Events.TranslationApplied recorded on ';
        $message .= $domainMessage->getRecordedOn()->toString();

        $this->logger->expects($this->once())->method('error')
            ->with($message);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_projects_the_update_of_a_translation_applied()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventId = new StringLiteral($id);
        $language = new Language('en');

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $title = new StringLiteral('Horror movie');
        $longDescription = new StringLiteral('This is a long, long, long, very long description.');
        $shortDescription = new StringLiteral('This is a short description.');

        $translationApplied = new TranslationApplied(
            $eventId,
            $language,
            $title,
            $shortDescription,
            $longDescription
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $metadata);
        $this->projector->handle($domainMessage);

        $title = new StringLiteral('Horror movie updated');
        $longDescription = new StringLiteral('This is a long, long, long, very long description updated.');
        $shortDescription = new StringLiteral('This is a short description updated.');

        $translationApplied = new TranslationApplied(
            $eventId,
            $language,
            $title,
            $shortDescription,
            $longDescription
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-translation-applied-en-updated.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_deletion_of_a_translation()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventId = new StringLiteral($id);
        $language = new Language('en');

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $title = new StringLiteral('Horror movie');
        $longDescription = new StringLiteral('This is a long, long, long, very long description.');
        $shortDescription = new StringLiteral('This is a short description.');

        $translationApplied = new TranslationApplied(
            $eventId,
            $language,
            $title,
            $shortDescription,
            $longDescription
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $metadata);
        $this->projector->handle($domainMessage);

        $languageFR = new Language('fr');
        $title = new StringLiteral('Filme d\'horreur');
        $longDescription = new StringLiteral('Une description qui est assez longue......');
        $shortDescription = new StringLiteral('Description courte');

        $translationApplied = new TranslationApplied(
            $eventId,
            $languageFR,
            $title,
            $shortDescription,
            $longDescription
        );

        $domainMessage = $this->createDomainMessage($id, $translationApplied, $metadata);
        $this->projector->handle($domainMessage);

        $translationDeleted = new TranslationDeleted(
            $eventId,
            $language
        );

        $domainMessage = $this->createDomainMessage($id, $translationDeleted, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-en-translation-removed.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_title_translation_addition()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $language = new Language('en');
        $title = new StringLiteral('Horror movie');

        $titleTranslated = new TitleTranslated(
            $id,
            $language,
            $title
        );

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $titleTranslated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-title-translated-to-en.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_title_translation_update()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $language = new Language('en');

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $title = new StringLiteral('Horror movie');

        $titleTranslated = new TitleTranslated(
            $id,
            $language,
            $title
        );

        $domainMessage = $this->createDomainMessage($id, $titleTranslated, $metadata);
        $this->projector->handle($domainMessage);

        $title = new StringLiteral('Horror movie updated');

        $titleTranslated = new TitleTranslated(
            $id,
            $language,
            $title
        );

        $domainMessage = $this->createDomainMessage($id, $titleTranslated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-title-translated-to-en-updated.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_description_translated()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $language = new Language('en');
        $description = new StringLiteral('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');

        $descriptionTranslated = new DescriptionTranslated(
            $id,
            $language,
            $description
        );

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461155055',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $descriptionTranslated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-description-translated-to-en.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_update_of_a_description_translated()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $language = new Language('en');

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461155055',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $description = new StringLiteral('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');

        $descriptionTranslated = new DescriptionTranslated(
            $id,
            $language,
            $description
        );

        $domainMessage = $this->createDomainMessage($id, $descriptionTranslated, $metadata);
        $this->projector->handle($domainMessage);

        $description = new StringLiteral('Description updated.');

        $descriptionTranslated = new DescriptionTranslated(
            $id,
            $language,
            $description
        );

        $domainMessage = $this->createDomainMessage($id, $descriptionTranslated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-description-translated-to-en-updated.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_description()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

        $descriptionUpdated = new DescriptionUpdated(
            $id,
            $description
        );

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461155055',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $descriptionUpdated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-description.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_label_added()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $labelAdded = new LabelAdded($id, new Label('foobar'));

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461164633',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $labelAdded, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-keyword.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_label_added_with_the_visible_attribute()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $labelAdded = new LabelAdded($id, new Label('foobar', false));

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461164633',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $labelAdded, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-keyword-visible-false.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461164633',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $labelAdded, $metadata);
        $this->projector->handle($domainMessage);

        // Add the label again.
        $labelAdded = new LabelAdded($id, new Label('foobar'));
        $domainMessage = $this->createDomainMessage($id, $labelAdded, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-keyword.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_a_label_deleted()
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

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_update_of_booking_info()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461164633',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $bookingInfo = new BookingInfo(
            'http://tickets.example.com',
            'Tickets on Example.com',
            '+32 666 666',
            'tickets@example.com',
            '2014-01-31T12:00:00',
            '2014-02-20T15:00:00',
            'booking name'
        );

        $bookingInfoUpdated = new BookingInfoUpdated(
            $id,
            $bookingInfo
        );

        $domainMessage = $this->createDomainMessage($id, $bookingInfoUpdated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-booking-info-updated.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_the_update_of_a_contact_point()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461164633',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

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

        $domainMessage = $this->createDomainMessage($id, $contactPointUpdated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-contact-point-updated.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find organizer with id ORG-123 when applying organizer updated on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);

        $this->logger->expects($this->once())->method('warning')
            ->with('Could not find location with id LOCATION-MISSING when setting location on event 404EE8DE-E828-9C07-FE7D12DC4EB24480.');

        $this->projector->handle($domainMessage);

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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    public function it_should_set_a_cdbid_on_every_location_label_when_importing_udb2_actor_places_as_events()
    {
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            '061C13AC-A15F-F419-D8993D68C9E94548',
            file_get_contents(__DIR__ . '/Repository/samples/place-actor.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $id = '061C13AC-A15F-F419-D8993D68C9E94548';

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('place-event-with-location-label-cdbid.xml')
        );

        $domainMessage = $this->createDomainMessage($id, $placeImportedFromUDB2, $this->metadata);

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
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

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * Helper function to create an event.
     * @param bool $theme   Whether or not to add a theme to the event
     */
    public function createEvent($theme = true)
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
    }

    /**
     * Helper function to create a place.
     */
    public function createPlace()
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
    }
}
