<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\CdbXmlDocumentSpecificationInterface;

/**
 * Class BroadcastingDocumentRepositoryDecorator
 *  This decorator will broadcast an event every time a document is saved
 * @package CultuurNet\UDB3\CdbXmlService\ReadModel\Repository
 */
class BroadcastingDocumentRepositoryDecorator extends DocumentRepositoryDecorator
{
    /**
     * @var DocumentEventFactoryInterface
     */
    protected $eventFactory;

    /**
     * @var EventBusInterface
     */
    protected $eventBus;

    /**
     * @var CdbXmlDocumentSpecificationInterface
     */
    protected $cdbXmlDocumentSpecification;

    public function __construct(
        DocumentRepositoryInterface $repository,
        EventBusInterface $eventBus,
        DocumentEventFactoryInterface $eventFactory,
        CdbXmlDocumentSpecificationInterface $cdbXmlDocumentSpecification
    ) {
        parent::__construct($repository);
        $this->eventFactory = $eventFactory;
        $this->eventBus = $eventBus;
        $this->cdbXmlDocumentSpecification = $cdbXmlDocumentSpecification;
    }

    /**
     * @param CdbXmlDocument $document
     */
    public function save(CdbXmlDocument $document)
    {
        parent::save($document);


        if ($this->cdbXmlDocumentSpecification->isSatisfiedBy($document)) {
            $event = $this->eventFactory->createEvent($document);

            $this->broadcastDocumentUpdated($event);
        }
    }

    /**
     * @param $event
     */
    protected function broadcastDocumentUpdated($event)
    {
        $generator = new Version4Generator();
        $events = [
            DomainMessage::recordNow(
                $generator->generate(),
                1,
                new Metadata(),
                $event
            ),
        ];

        $this->eventBus->publish(
            new DomainEventStream($events)
        );
    }
}
