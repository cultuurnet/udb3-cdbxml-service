<?php

namespace CultuurNet\UDB3\CDBXMLService\Events;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\CDBXMLService\ReadModel\Repository\CDBXMLDocument;
use CultuurNet\UDB3\CDBXMLService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated as EventOrganizerUpdated;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated as PlaceOrganizerUpdated;

class EventEnricherTest extends \PHPUnit_Framework_TestCase
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
     * @var EventEnricher
     */
    private $enricher;

    public function setUp()
    {
        $this->eventBus = $this->getMock(EventBusInterface::class);
        $this->organizerRepository = $this->getMock(DocumentRepositoryInterface::class);

        $this->enricher = new EventEnricher(
            $this->eventBus,
            $this->organizerRepository
        );
    }

    /**
     * @test
     * @dataProvider organizerUpdatedDataProvider
     *
     * @param EventOrganizerUpdated|PlaceOrganizerUpdated $organizerUpdated
     * @param EnrichedOrganizerUpdated $expectedEnrichedOrganizerUpdated
     */
    public function it_enriches_organizer_updated_events(
        $organizerUpdated,
        EnrichedOrganizerUpdated $expectedEnrichedOrganizerUpdated
    ) {
        $itemId = $organizerUpdated->getItemId();
        $organizerId = $organizerUpdated->getOrganizerId();

        $organizerDocument = new CDBXMLDocument(
            $organizerId,
            $this->getCDBXML($organizerId)
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
            ->method('dispatch')
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
        return [
            [
                new EventOrganizerUpdated('item-id', '1'),
                new EnrichedOrganizerUpdated('item-id', '1', 'Foo'),
            ],
        ];
    }

    /**
     * @param string $organizerId
     * @return string
     */
    private function getCDBXML($organizerId)
    {
        return file_get_contents(__DIR__ . '/data/actor-' . $organizerId . '.xml');
    }
}
