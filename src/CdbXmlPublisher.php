<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\BroadwayAMQP\SpecificationInterface;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use DateTimeImmutable;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\Web\Url;

class CDBXMLPublisher implements CdbXmlPublisherInterface
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
     */
    public function __construct(
        IriGeneratorInterface $iriGenerator,
        EventBusInterface $eventBus
    ) {
        $this->eventBus = $eventBus;
        $this->iriGenerator = $iriGenerator;
    }

    public function publish(
        CdbXmlDocument $cdbXmlDocument,
        DomainMessage $domainMessage
    ) {
        $id = $cdbXmlDocument->getId();
        $location = $this->iriGenerator->iri($id);
        $authorId = $domainMessage->getMetadata()->serialize()['user_id'];

        // todo: implement specification
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
            new Metadata([]),
            $event,
            DateTime::now()
        );

        $this->eventBus->publish(new DomainEventStream([$message]));
    }
}
