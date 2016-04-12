<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactory;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;

class OrganizerToActorCdbXmlProjectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayCache
     */
    private $cache;

    /**
     * @var CacheDocumentRepository
     */
    private $repository;

    /**
     * @var CdbXmlPublisherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cdbXmlPublisher;

    /**
     * @var OrganizerToActorCdbXmlProjector
     */
    private $projector;

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $this->cdbXmlPublisher = $this->getMock(CdbXmlPublisherInterface::class);

        $this->projector = (
            new OrganizerToActorCdbXmlProjector(
                $this->repository,
                new CdbXmlDocumentFactory('3.3'),
                new AddressFactory()
            )
        )->withCdbXmlPublisher($this->cdbXmlPublisher);
    }

    /**
     * @test
     */
    public function it_projects_organizer_created()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $event = new OrganizerCreated(
            $id,
            new Title('DE Studio'),
            [
                new Address(
                    'Maarschalk Gerardstraat 4',
                    '2000',
                    'Antwerpen',
                    'BE'
                ),
            ],
            ['+32 3 260 96 10'],
            ['info@villanella.be'],
            ['http://www.destudio.com']
        );

        $domainMessage = $this->createDomainMessage($id, $event);

        $expectedCdbXml = file_get_contents(__DIR__ . '/Repository/samples/actor-with-contact-info.xml');
        $expectedCdbXmlDocument = new CdbXmlDocument($id, $expectedCdbXml);

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @param string $entityId
     * @param object $event
     * @param Metadata|null $metadata
     * @param DateTime $dateTime
     * @return DomainMessage
     */
    private function createDomainMessage(
        $entityId,
        $event,
        Metadata $metadata = null,
        DateTime $dateTime = null
    ) {
        if (is_null($metadata)) {
            $metadata = new Metadata();
        }

        if (is_null($dateTime)) {
            $dateTime = DateTime::now();
        }

        return new DomainMessage(
            $entityId,
            1,
            $metadata,
            $event,
            $dateTime
        );
    }

    /**
     * @param CdbXmlDocument $expectedCdbXmlDocument
     * @param DomainMessage $domainMessage
     */
    private function expectCdbXmlDocumentToBePublished(
        CdbXmlDocument $expectedCdbXmlDocument,
        DomainMessage $domainMessage
    ) {
        $this->cdbXmlPublisher->expects($this->once())
            ->method('publish')
            ->with($expectedCdbXmlDocument, $domainMessage);
    }

    /**
     * @param CdbXmlDocument $expectedCdbXmlDocument
     */
    private function assertCdbXmlDocumentInRepository(CdbXmlDocument $expectedCdbXmlDocument)
    {
        $cdbId = $expectedCdbXmlDocument->getId();
        $actualCdbXmlDocument = $this->repository->get($cdbId);

        if (is_null($actualCdbXmlDocument)) {
            $this->fail("CdbXmlDocument for CdbId {$cdbId} not found.");
        }

        $this->assertEquals($expectedCdbXmlDocument->getCdbXml(), $actualCdbXmlDocument->getCdbXml());
    }
}
