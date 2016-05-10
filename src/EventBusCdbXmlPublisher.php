<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
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
    private $newPublication;

    /**
     * CDBXMLPublisher constructor.
     * @param EventBusInterface $eventBus
     */
    public function __construct(
        EventBusInterface $eventBus
    ) {
        $this->eventBus = $eventBus;
        $this->newPublication = new NewPublication();
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

        if ($this->newPublication->isSatisfiedBy($domainMessage)) {
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
