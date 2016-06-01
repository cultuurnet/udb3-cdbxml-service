<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB2DomainEvents\ActorUpdated;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\CdbXmlService\CdbXml\Specification\ActorCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXml\Specification\EventCdbXml;
use CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification\NewActorPublication;
use CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification\NewEventPublication;
use CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification\UpdatedActorPublication;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use DateTimeImmutable;
use InvalidArgumentException;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\Web\Url;

class EventBusCdbXmlPublisher implements CdbXmlPublisherInterface
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var SpecificationInterface
     */
    private $newEventPublication;

    /**
     * @var SpecificationInterface
     */
    private $updatedActorPublication;

    /**
     * @var SpecificationInterface
     */
    private $newActorPublication;

    /**
     * CDBXMLPublisher constructor.
     * @param EventBusInterface $eventBus
     */
    public function __construct(
        EventBusInterface $eventBus
    ) {
        $this->eventBus = $eventBus;
        $this->newEventPublication = new NewEventPublication();
        $this->newActorPublication = new NewActorPublication();
        $this->updatedActorPublication = new UpdatedActorPublication();
    }

    public function publish(
        CdbXmlDocument $cdbXmlDocument,
        DomainMessage $domainMessage
    ) {
        $id = $cdbXmlDocument->getId();

        // Author id can be empty in metadata if event is
        // Event/PlaceImportedFromUDB2 or Event/PlaceUpdatedFromUDB2.
        $metadata = $domainMessage->getMetadata()->serialize();
        $authorId = isset($metadata['user_id']) ? $metadata['user_id'] : '';

        if (!isset($metadata['id'])) {
            throw new InvalidArgumentException('An id metadata property is required to determine the publication location.');
        }

        $location = $metadata['id'];

        if ($this->newActorPublication->isSatisfiedBy($domainMessage)) {
            $event = new ActorCreated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        } elseif ($this->updatedActorPublication->isSatisfiedBy($domainMessage)) {
            $event = new ActorUpdated(
                new StringLiteral($id),
                new DateTimeImmutable(),
                new StringLiteral($authorId),
                Url::fromNative($location)
            );
        } elseif ($this->newEventPublication->isSatisfiedBy($domainMessage)) {
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

        $message = new DomainMessage(
            UUID::generateAsString(),
            0,
            $domainMessage->getMetadata(),
            $event,
            $domainMessage->getRecordedOn()
        );

        $this->eventBus->publish(new DomainEventStream([$message]));
    }
}
