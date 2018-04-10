<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\Description\JsonLdDescriptionToCdbXmlLongDescriptionFilter;
use CultuurNet\UDB3\Cdb\Description\JsonLdDescriptionToCdbXmlShortDescriptionFilter;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Location\Location;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use Psr\Log\LoggerInterface;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Url;
use ValueObjects\StringLiteral\StringLiteral;

class RelationsToCdbXmlProjectorTest extends CdbXmlProjectorTestBase
{
    /**
     * @var OfferToCdbXmlProjector
     */
    protected $projector;

    /**
     * @var RelationsToCdbXmlProjector
     */
    private $relationsProjector;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var CacheDocumentRepository
     */
    private $actorRepository;

    /**
     * @var OfferRelationsServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $offerRelationsService;

    /**
     * @var IriOfferIdentifierFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $iriOfferIdentifierFactory;

    public function setUp()
    {
        parent::setUp();
        $this->setCdbXmlFilesPath(__DIR__ . '/Repository/samples/');

        date_default_timezone_set('Europe/Brussels');

        $this->actorRepository = new CacheDocumentRepository($this->cache);

        $this->projector = new OfferToCdbXmlProjector(
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
            new EventCdbIdExtractor()
        );

        $this->offerRelationsService = $this->createMock(OfferRelationsServiceInterface::class);

        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);

        $this->relationsProjector = new RelationsToCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            new MetadataCdbItemEnricher(
                new CdbXmlDateFormatter()
            ),
            $this->actorRepository,
            $this->offerRelationsService,
            $this->iriOfferIdentifierFactory
        );

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
     */
    public function it_embeds_the_projection_of_an_organizer_in_all_related_offers()
    {
        // Add a first event.
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $this->createEvent($id);

        // Add a second event.
        $secondId = 'EVENT-ABC-123';
        $this->createEvent($secondId);

        // create an organizer.
        $organizerId = 'ORG-123';
        $organizerCdbxml = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->actorRepository->save($organizerCdbxml);

        $this->offerRelationsService
            ->expects($this->once())
            ->method('getByOrganizer')
            ->with($organizerId)
            ->willReturn(
                [
                    $id,
                    $secondId,
                ]
            );

        $organizerProjectedToCdbXml = new OrganizerProjectedToCdbXml(
            $organizerId
        );

        $organizerMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/' . $organizerId,
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $organizerProjectedToCdbXml, $organizerMetadata);
        $this->relationsProjector->handle($domainMessage);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-organizer-1.xml')
        );

        $expectedSecondCdbXmlDocument = new CdbXmlDocument(
            $secondId,
            $this->loadCdbXmlFromFile('event-with-organizer-2.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedSecondCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_logs_an_alert_when_event_was_not_found_based_on_organizer_relation()
    {
        // Add a first event.
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $this->createEvent($id);

        // But don't add a second event to force logging an alert
        $secondId = 'EVENT-ABC-123';

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $this->relationsProjector->setLogger($logger);
        $logger->expects($this->once())
            ->method('alert')
            ->with(
                'Unable to load cdbxml of event with id {event_id}',
                [
                    'event_id' => $secondId,
                ]
            );

        // create an organizer.
        $organizerId = 'ORG-123';
        $organizerCdbxml = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->actorRepository->save($organizerCdbxml);

        $this->offerRelationsService
            ->expects($this->once())
            ->method('getByOrganizer')
            ->with($organizerId)
            ->willReturn(
                [
                    $id,
                    $secondId,
                ]
            );

        $organizerProjectedToCdbXml = new OrganizerProjectedToCdbXml(
            $organizerId
        );

        $organizerMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/' . $organizerId,
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $organizerProjectedToCdbXml, $organizerMetadata);
        $this->relationsProjector->handle($domainMessage);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-organizer-1.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_embeds_the_projection_of_a_place_in_all_events_located_at_that_place()
    {
        // Add a first event.
        $eventId = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $this->createEvent($eventId);

        // Add a second event.
        $secondEventId = 'EVENT-ABC-123';
        $includeTheme = true;
        $includeContactPoint = true;
        $this->createEvent($secondEventId, $includeTheme, $includeContactPoint);

        $placeId = 'C4ACF936-1D5F-48E8-B2EC-863B313CBDE6';
        // Create a place.
        $this->createPlace($placeId);
        $placeIri = Url::fromNative('http://foo.bar/place/' . $placeId);

        $placeIdentifier = new IriOfferIdentifier($placeIri, $placeId, OfferType::PLACE());

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($placeIri)
            ->willReturn($placeIdentifier);

        $this->offerRelationsService
            ->expects($this->once())
            ->method('getByPlace')
            ->with($placeId)
            ->willReturn(
                [
                    $eventId,
                    $secondEventId,
                ]
            );


        $placeProjectedToCdbXml = new PlaceProjectedToCdbXml(
            $placeId,
            $placeIri
        );

        $placeMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710917',
                'id' => 'http://foo.be/item/' . $placeId,
            ]
        );

        $domainMessage = $this->createDomainMessage($placeId, $placeProjectedToCdbXml, $placeMetadata);
        $this->relationsProjector->handle($domainMessage);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $eventId,
            $this->loadCdbXmlFromFile('event-with-place.xml')
        );

        $expectedSecondCdbXmlDocument = new CdbXmlDocument(
            $secondEventId,
            $this->loadCdbXmlFromFile('event-with-place-2.xml')
        );

        //$this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedSecondCdbXmlDocument);
    }

    /**
     * Helper function to create an event.
     * @param string $eventId
     * @param bool $theme Whether or not to add a theme to the event
     * @param bool $contactPoint
     */
    public function createEvent($eventId, $theme = true, $contactPoint = false)
    {
        $timestamps = [
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T12:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T15:00:00+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T12:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T15:00:00+01:00')
            ),
        ];

        $eventMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/' . $eventId,
            ]
        );

        $placeId = '34973B89-BDA3-4A79-96C7-78ACC022907D';

        $placeMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/' . $placeId,
            ]
        );

        $placeCreated = new PlaceCreated(
            $placeId,
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
        $domainMessage = $this->createDomainMessage($placeId, $placeCreated, $placeMetadata);
        $this->projector->handle($domainMessage);

        $event = new EventCreated(
            $eventId,
            new Language('nl'),
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            new Location(
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                new StringLiteral('Bibberburcht'),
                new Address(
                    new Street('Bondgenotenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            ),
            new Calendar(
                CalendarType::MULTIPLE(),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-01-31T13:00:00+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2014-02-20T16:00:00+01:00'),
                $timestamps
            ),
            $theme ? new Theme('1.7.6.0.0', 'Griezelfilm of horror'):null
        );

        $domainMessage = $this->createDomainMessage($eventId, $event, $eventMetadata);

        $this->projector->handle($domainMessage);

        if ($contactPoint) {
            $contactPointUpdated = new ContactPointUpdated(
                $eventId,
                new ContactPoint(
                    ['+32 444 44 44 44'],
                    ['test@foo.bar'],
                    ['https://foo.bar']
                )
            );

            $domainMessage = $this->createDomainMessage($eventId, $contactPointUpdated, $eventMetadata);

            $this->projector->handle($domainMessage);
        }
    }

    /**
     * Helper function to create a place.
     * @param string $id
     */
    public function createPlace($id = '34973B89-BDA3-4A79-96C7-78ACC022907D')
    {
        $place = new PlaceCreated(
            $id,
            new Language('nl'),
            new Title('My Place'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('Bondgenotenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $domainMessage = $this->createDomainMessage($id, $place, $this->metadata);

        $this->projector->handle($domainMessage);
    }
}
