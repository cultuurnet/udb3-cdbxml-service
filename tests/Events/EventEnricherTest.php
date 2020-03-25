<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated as EventOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated as PlaceOrganizerUpdated;
use PHPUnit\Framework\TestCase;

class EventEnricherTest extends TestCase
{
    /**
     * @var EventBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventBus;

    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $organizerRepository;

    /**
     * @var ActorItemFactory
     */
    private $actorItemFactory;

    /**
     * @var EventEnricher
     */
    private $enricher;

    public function setUp()
    {
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->organizerRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->actorItemFactory = new ActorItemFactory(
            \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
        );

        $this->enricher = new EventEnricher(
            $this->eventBus,
            $this->organizerRepository,
            $this->actorItemFactory
        );
    }

    /**
     * @test
     * @dataProvider organizerUpdatedDataProvider
     *
     * @param AbstractOrganizerUpdated $organizerUpdated
     * @param EnrichedOrganizerUpdated $expectedEnrichedOrganizerUpdated
     */
    public function it_enriches_organizer_updated_events(
        $organizerUpdated,
        EnrichedOrganizerUpdated $expectedEnrichedOrganizerUpdated
    ) {
        $itemId = $organizerUpdated->getItemId();
        $organizerId = $organizerUpdated->getOrganizerId();

        $organizerDocument = new CdbXmlDocument(
            $organizerId,
            file_get_contents(__DIR__ . '/samples/actor.xml')
        );

        $this->organizerRepository->expects($this->once())
            ->method('get')
            ->with($organizerId)
            ->willReturn($organizerDocument);

        $playhead = 1;
        $metadata = new Metadata(['user' => 'some-user-id']);
        $recordedOn = DateTime::fromString('2016-03-29 16:33:39.814545');

        $expectedEnrichedDomainMessage = new DomainMessage(
            $itemId,
            $playhead,
            $metadata,
            $expectedEnrichedOrganizerUpdated,
            $recordedOn
        );

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with(new DomainEventStream([$expectedEnrichedDomainMessage]));

        $originalDomainMessage = new DomainMessage(
            $itemId,
            $playhead,
            $metadata,
            $organizerUpdated,
            $recordedOn
        );

        $this->enricher->handle($originalDomainMessage);
    }

    /**
     * @return array
     */
    public function organizerUpdatedDataProvider()
    {
        $eventOrganizerUpdated = new EventOrganizerUpdated(
            'item-id',
            '404EE8DE-E828-9C07-FE7D12DC4EB24480'
        );

        $enrichedEventOrganizerUpdated = new EnrichedOrganizerUpdated(
            $eventOrganizerUpdated,
            'DE Studio'
        );

        $placeOrganizerUpdated = new PlaceOrganizerUpdated(
            'item-id',
            '404EE8DE-E828-9C07-FE7D12DC4EB24480'
        );
        $enrichedPlaceOrganizerUpdated = new EnrichedOrganizerUpdated(
            $placeOrganizerUpdated,
            'DE Studio'
        );

        return [
            [
                $eventOrganizerUpdated,
                $enrichedEventOrganizerUpdated,
            ],
            [
                $placeOrganizerUpdated,
                $enrichedPlaceOrganizerUpdated,
            ],
        ];
    }
}
