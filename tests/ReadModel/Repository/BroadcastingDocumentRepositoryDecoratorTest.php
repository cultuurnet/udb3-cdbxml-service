<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\CdbXmlDocumentSpecificationInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\OfferDocumentMetadataFactory;

class BroadcastingDocumentRepositoryDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventBus;

    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $decoratedRepository;

    /**
     * @var BroadcastingDocumentRepositoryDecorator
     */
    protected $repository;

    /**
     * @var DocumentEventFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventFactory;

    /**
     * @var OfferDocumentMetadataFactory
     */
    protected $offerDocumentMetadataFactory;

    /**
     * @var CdbXmlDocumentSpecificationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cdbXmlDocumentSpecification;

    public function setUp()
    {
        $this->decoratedRepository = $this->getMock(DocumentRepositoryInterface::class);
        $this->eventBus = $this->getMock(EventBusInterface::class);
        $this->eventFactory = $this->getMock(DocumentEventFactoryInterface::class);
        $this->cdbXmlDocumentSpecification = $this->getMock(CdbXmlDocumentSpecificationInterface::class);
        $this->offerDocumentMetadataFactory = new OfferDocumentMetadataFactory();

        $this->repository = new BroadcastingDocumentRepositoryDecorator(
            $this->decoratedRepository,
            $this->eventBus,
            $this->eventFactory,
            $this->cdbXmlDocumentSpecification,
            $this->offerDocumentMetadataFactory
        );
    }

    /**
     * @test
     */
    public function it_broadcasts_when_a_document_is_saved()
    {
        $document = new CdbXmlDocument(
            '34973B89-BDA3-4A79-96C7-78ACC022907D',
            file_get_contents(__DIR__ . '/samples/place.xml')
        );

        $this->cdbXmlDocumentSpecification->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($document)
            ->willReturn(true);

        // the provided factory should be used to create a new event
        $this->eventFactory->expects($this->once())
            ->method('createEvent')
            ->with($document);

        // when saving the event it should also save the document in the decorated repository
        $this->decoratedRepository->expects($this->once())
            ->method('save')
            ->with($document);

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->repository->save($document);
    }

    /**
     * @test
     */
    public function it_can_remove_a_document()
    {
        $id = '34973B89-BDA3-4A79-96C7-78ACC022907D';

        // when removing the document it should also remove the document in the decorated repository
        $this->decoratedRepository->expects($this->once())
            ->method('remove')
            ->with($id);

        $this->repository->remove($id);
    }

    /**
     * @test
     */
    public function it_can_get_a_document()
    {
        $id = '34973B89-BDA3-4A79-96C7-78ACC022907D';

        // when getting the document it should also get the document in the decorated repository
        $this->decoratedRepository->expects($this->once())
            ->method('get')
            ->with($id);

        $this->repository->get($id);
    }
}
