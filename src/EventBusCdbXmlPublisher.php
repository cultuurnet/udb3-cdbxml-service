<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB2DomainEvents\ActorUpdated;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\CdbXmlService\Events\EventProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\Web\Url;

class EventBusCdbXmlPublisher implements EventListenerInterface
{
    use LoggerAwareTrait;

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var IriOfferIdentifierFactory
     */
    private $iriOfferIdentifierFactory;

    /**
     * @param EventBusInterface $eventBus
     * @param IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
     */
    public function __construct(
        EventBusInterface $eventBus,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
    ) {
        $this->eventBus = $eventBus;
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
        $this->logger = new NullLogger();
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $handlers = [
            EventProjectedToCdbXml::class => 'applyEventProjectedToCdbXml',
            PlaceProjectedToCdbXml::class => 'applyPlaceProjectedToCdbXml',
            OrganizerProjectedToCdbXml::class => 'applyOrganizerProjectedToCdbXml',
        ];

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];
            $this->{$handler}($payload, $domainMessage);
        }
    }

    /**
     * @param PlaceProjectedToCdbXml $placeProjectedToCdbXml
     * @param DomainMessage $domainMessage
     */
    public function applyPlaceProjectedToCdbXml(
        PlaceProjectedToCdbXml $placeProjectedToCdbXml,
        DomainMessage $domainMessage
    ) {
        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative((string) $placeProjectedToCdbXml->getIri())
        );

        $id = $identifier->getId();

        // Author id can be empty in metadata if event is
        // Event/PlaceImportedFromUDB2 or Event/PlaceUpdatedFromUDB2.
        $metadata = $domainMessage->getMetadata()->serialize();
        $authorId = isset($metadata['user_id']) ? $metadata['user_id'] : '';

        if (!isset($metadata['id'])) {
            throw new InvalidArgumentException('An id metadata property is required to determine the publication location.');
        }

        $location = $metadata['id'];

        if ($placeProjectedToCdbXml->isNew()) {
            $event = new ActorCreated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        } else {
            $event = new ActorUpdated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        }

        if ($event) {
            $this->publish($event, $id, $domainMessage);
        } else {
            $this->logger->warning(
                'failed to determine udb2 domain message for cdbxml document ' . $id
            );
        }
    }

    /**
     * @param EventProjectedToCdbXml $eventProjectedToCdbXml
     * @param DomainMessage $domainMessage
     */
    public function applyEventProjectedToCdbXml(
        EventProjectedToCdbXml $eventProjectedToCdbXml,
        DomainMessage $domainMessage
    ) {
        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative((string) $eventProjectedToCdbXml->getIri())
        );

        $id = $identifier->getId();

        // Author id can be empty in metadata if event is
        // Event/PlaceImportedFromUDB2 or Event/PlaceUpdatedFromUDB2.
        $metadata = $domainMessage->getMetadata()->serialize();
        $authorId = isset($metadata['user_id']) ? $metadata['user_id'] : '';

        if (!isset($metadata['id'])) {
            throw new InvalidArgumentException('An id metadata property is required to determine the publication location.');
        }

        $location = $metadata['id'];

        if ($eventProjectedToCdbXml->isNew()) {
            $event = new EventCreated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        } else {
            $event = new EventUpdated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        }

        if ($event) {
            $this->publish($event, $id, $domainMessage);
        } else {
            $this->logger->warning(
                'failed to determine udb2 domain message for cdbxml document ' . $id
            );
        }
    }

    public function applyOrganizerProjectedToCdbXml(
        OrganizerProjectedToCdbXml $organizerProjectedToCdbXml,
        DomainMessage $domainMessage
    ) {
        $id = $organizerProjectedToCdbXml->getOrganizerId();

        // Author id can be empty in metadata if event is
        // Event/PlaceImportedFromUDB2 or Event/PlaceUpdatedFromUDB2.
        $metadata = $domainMessage->getMetadata()->serialize();
        $authorId = isset($metadata['user_id']) ? $metadata['user_id'] : '';

        if (!isset($metadata['id'])) {
            throw new InvalidArgumentException('An id metadata property is required to determine the publication location.');
        }

        $location = $metadata['id'];

        if ($organizerProjectedToCdbXml->isNew()) {
            $event = new ActorCreated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        } else {
            $event = new ActorUpdated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        }

        if ($event) {
            $this->publish($event, $id, $domainMessage);
        } else {
            $this->logger->warning(
                'failed to determine udb2 domain message for cdbxml document ' . $id
            );
        }
    }

    /**
     * @param $event
     * @param string $id
     * @param DomainMessage $domainMessage
     */
    private function publish($event, $id, DomainMessage $domainMessage)
    {
            $message = new DomainMessage(
                UUID::generateAsString(),
                0,
                $domainMessage->getMetadata(),
                $event,
                $domainMessage->getRecordedOn()
            );

            $this->logger->info(
                'publishing message ' . get_class($event) . ' for cdbid ' . $id . ' on internal event bus'
            );

            $this->eventBus->publish(new DomainEventStream([$message]));
    }
}
