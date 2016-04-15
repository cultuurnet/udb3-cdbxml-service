<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated as EventOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated as PlaceOrganizerUpdated;

/**
 * Enrich UDB3 domain events with additional data required to project to CDBXML.
 */
class EventEnricher implements EventListenerInterface
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var DocumentRepositoryInterface
     */
    private $organizerRepository;

    /**
     * @var ActorItemFactoryInterface
     */
    private $actorItemFactory;

    private static $eventHandlers = [
        EventOrganizerUpdated::class => 'enrichOrganizerUpdated',
        PlaceOrganizerUpdated::class => 'enrichOrganizerUpdated',
    ];

    /**
     * @param EventBusInterface $eventBus
     * @param DocumentRepositoryInterface $organizerRepository
     * @param ActorItemFactoryInterface $actorItemFactory
     */
    public function __construct(
        EventBusInterface $eventBus,
        DocumentRepositoryInterface $organizerRepository,
        ActorItemFactoryInterface $actorItemFactory
    ) {
        $this->eventBus = $eventBus;
        $this->organizerRepository = $organizerRepository;
        $this->actorItemFactory = $actorItemFactory;
    }

    /**
     * Enriches the payload of specific Domain Messages and re-publishes them on an event bus.
     *
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $className = get_class($domainMessage->getPayload());

        if (isset(self::$eventHandlers[$className])) {
            $handler = self::$eventHandlers[$className];
            $enrichedPayload = $this->{$handler}(
                $domainMessage->getPayload()
            );

            $enrichedMessage = new DomainMessage(
                $domainMessage->getId(),
                $domainMessage->getPlayhead(),
                $domainMessage->getMetadata(),
                $enrichedPayload,
                $domainMessage->getRecordedOn()
            );

            $this->eventBus->publish(
                new DomainEventStream([$enrichedMessage])
            );
        }
    }

    /**
     * @param AbstractOrganizerUpdated $organizerUpdated
     * @return EnrichedOrganizerUpdated
     */
    private function enrichOrganizerUpdated(AbstractOrganizerUpdated $organizerUpdated)
    {
        return new EnrichedOrganizerUpdated(
            $organizerUpdated,
            $this->getOrganizerName(
                $organizerUpdated->getOrganizerId()
            )
        );
    }

    /**
     * @param string $organizerId
     * @return string
     */
    private function getOrganizerName($organizerId)
    {
        $name = '';
        $organizerDocument = $this->organizerRepository->get($organizerId);

        $organizer = $this->actorItemFactory->createFromCdbXml(
            $organizerDocument->getCdbXml()
        );

        /** @var \CultureFeed_Cdb_Data_Detail[] $details */
        $details = $organizer->getDetails();
        foreach ($details as $languageDetail) {
            // Organizer name is not translatable in Event CDBXML and we don't have a "base language" so we just pick
            // the first one we find.
            $name = $languageDetail->getTitle();
            break;
        }

        return $name;
    }
}
