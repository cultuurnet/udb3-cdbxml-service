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
use CultuurNet\UDB3\Event\Events\ContentTypes as EventContentTypes;
use CultuurNet\UDB3\Place\Events\ContentTypes as PlaceContentTypes;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
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
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var SpecificationInterface
     */
    private $newPublication;

    /**
     * CDBXMLPublisher constructor.
     * @param IriGeneratorInterface $iriGenerator
     * @param EventBusInterface $eventBus
     */
    public function __construct(
        IriGeneratorInterface $iriGenerator,
        EventBusInterface $eventBus
    ) {
        $this->eventBus = $eventBus;
        $this->iriGenerator = $iriGenerator;
        $this->newPublication = new NewPublication();
    }

    public function publish(
        CdbXmlDocument $cdbXmlDocument,
        DomainMessage $domainMessage
    ) {
        $id = $this->identifyEventType($domainMessage) . '/' . $cdbXmlDocument->getId();
        $location = $this->iriGenerator->iri($id);
        $authorId = $domainMessage->getMetadata()->serialize()['user_id'];

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
            new Metadata($domainMessage->getMetadata()->serialize()),
            $event,
            $domainMessage->getRecordedOn()
        );

        $this->eventBus->publish(new DomainEventStream([$message]));
    }

    /**
     * @param DomainMessage $domainMessage
     * @return string
     * @throws InvalidArgumentException
     */
    private function identifyEventType(DomainMessage $domainMessage)
    {
        $typeMap = [
            'event' => EventContentTypes::map(),
            'place' => PlaceContentTypes::map(),
        ];
        $domainEvent = $domainMessage->getPayload();
        $eventClass = get_class($domainEvent);

        $findType = function ($eventType, $typeName) use ($typeMap, $eventClass) {
            if ($eventType) {
                return $eventType;
            } else {
                return array_key_exists($eventClass, $typeMap[$typeName]) ? $typeName : null;
            }
        };

        $type = array_reduce(array_keys($typeMap), $findType);

        if (!$type) {
            throw new InvalidArgumentException(
                'An offer type could not be determined for the domain-event with class: ' . $eventClass
            );
        }

        return $type;
    }
}
