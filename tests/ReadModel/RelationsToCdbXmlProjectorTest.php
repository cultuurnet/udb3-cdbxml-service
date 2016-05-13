<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactory;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\EventServiceInterface;
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\PlaceService;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class RelationsToCdbXmlProjectorTest extends CdbXmlProjectorTestBase
{
    /**
     * @var OfferToEventCdbXmlProjector
     */
    private $projector;

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
     * @var EventServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventService;

    /**
     * @var LocalPlaceService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $placeService;

    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $iriOfferIdentifierFactory;

    public function setUp()
    {
        parent::setUp();
        $this->setCdbXmlFilesPath(__DIR__ . '/Repository/samples/');

        date_default_timezone_set('Europe/Brussels');

        $this->actorRepository = new CacheDocumentRepository($this->cache);

        $this->projector = (
        new OfferToEventCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            new MetadataCdbItemEnricher(
                new CdbXmlDateFormatter()
            ),
            $this->actorRepository
        )
        )->withCdbXmlPublisher($this->cdbXmlPublisher);

        $this->eventService = $this->getMock(EventServiceInterface::class);

        $this->placeService = $this->getMock(LocalPlaceService::class, array(), array(), 'placeServiceMock', false);

        $this->iriOfferIdentifierFactory = $this->getMock(IriOfferIdentifierFactoryInterface::class);

        $this->relationsProjector = (
        new RelationsToCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            new MetadataCdbItemEnricher(
                new CdbXmlDateFormatter()
            ),
            $this->actorRepository,
            $this->eventService,
            $this->placeService,
            $this->iriOfferIdentifierFactory
        )
        )->withCdbXmlPublisher($this->cdbXmlPublisher);

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

        $this->eventService
            ->expects($this->once())
            ->method('eventsOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn(
                [
                    $id,
                    $secondId,
                ]
            );

        $this->placeService
            ->expects($this->once())
            ->method('placesOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn(
                []
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
            $this->loadCdbXmlFromFile('event-with-organizer.xml')
        );

        $expectedSecondCdbXmlDocument = new CdbXmlDocument(
            $secondId,
            $this->loadCdbXmlFromFile('event-with-organizer-2.xml')
        );

        //$this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
        $this->assertCdbXmlDocumentInRepository($expectedSecondCdbXmlDocument);
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
        $this->createEvent($secondEventId);

        // Create a place.
        $this->createPlace();
        $placeId = 'MY-PLACE-123';
        $placeIri = Url::fromNative('http://foo.bar/place/' . $placeId);

        $placeIdentifier = new IriOfferIdentifier($placeIri, $placeId, OfferType::PLACE());

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($placeIri)
            ->willReturn($placeIdentifier);

        $this->eventService
            ->expects($this->once())
            ->method('eventsLocatedAtPlace')
            ->with($placeId)
            ->willReturn(
                [
                    $eventId,
                    $secondEventId,
                ]
            );


        $placeProjectedToCdbXml = new PlaceProjectedToCdbXml(
            $placeIri
        );

        $placeMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
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
     * @param bool $theme   Whether or not to add a theme to the event
     */
    public function createEvent($eventId, $theme = true)
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

        $eventMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/' . $eventId,
            ]
        );

        $placeId = 'LOCATION-ABC-123';

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
            new Title('$name'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address('$street', '$postalCode', '$locality', '$country'),
            new Calendar('permanent')
        );
        $domainMessage = $this->createDomainMessage($placeId, $placeCreated, $placeMetadata);
        $this->projector->handle($domainMessage);

        $theme = $theme?new Theme('1.7.6.0.0', 'Griezelfilm of horror'):null;
        $event = new EventCreated(
            $eventId,
            new Title('Griezelfilm of horror'),
            new EventType('0.50.6.0.0', 'film'),
            new Location('LOCATION-ABC-123', '$name', '$country', '$locality', '$postalcode', '$street'),
            new Calendar('multiple', '2014-01-31T13:00:00+01:00', '2014-02-20T16:00:00+01:00', $timestamps),
            $theme
        );

        $domainMessage = $this->createDomainMessage($eventId, $event, $eventMetadata);

        $this->projector->handle($domainMessage);
    }

    /**
     * Helper function to create a place.
     */
    public function createPlace()
    {
        $id = 'MY-PLACE-123';

        $place = new PlaceCreated(
            $id,
            new Title('My Place'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address('Teststraat', '3000', 'Leuven', 'België'),
            new Calendar('permanent')
        );

        $domainMessage = $this->createDomainMessage($id, $place, $this->metadata);

        $this->projector->handle($domainMessage);
    }
}
