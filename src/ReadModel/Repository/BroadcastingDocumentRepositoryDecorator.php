<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\CdbXmlDocumentSpecificationInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\OfferDocumentMetadataFactory;

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
     * @var OfferDocumentMetadataFactory
     */
    protected $offerDocumentMetadataFactory;

    public function __construct(
        DocumentRepositoryInterface $repository,
        EventBusInterface $eventBus,
        DocumentEventFactoryInterface $eventFactory,
        OfferDocumentMetadataFactory $offerDocumentMetadataFactory
    ) {
        parent::__construct($repository);
        $this->eventFactory = $eventFactory;
        $this->eventBus = $eventBus;
        $this->offerDocumentMetadataFactory = $offerDocumentMetadataFactory;
    }

    /**
     * @param CdbXmlDocument $document
     */
    public function save(CdbXmlDocument $document)
    {
        $isNew = true;
        if (!empty($this->decoratedRepository->get($document->getId()))) {
            $isNew = false;
        }

        parent::save($document);

        $event = $this->eventFactory->createEvent($document, $isNew);
        $metadata = $this->offerDocumentMetadataFactory->createMetadata($document);

        $generator = new Version4Generator();
        $events = [
            DomainMessage::recordNow(
                $generator->generate(),
                1,
                $metadata,
                $event
            ),
        ];

        $this->eventBus->publish(
            new DomainEventStream($events)
        );
    }
}
