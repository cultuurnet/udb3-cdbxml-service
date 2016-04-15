<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactory;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
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

    /**
     * @var Metadata
     */
    private $metadata;

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $this->cdbXmlPublisher = $this->getMock(CdbXmlPublisherInterface::class);

        $this->projector = (
            new OrganizerToActorCdbXmlProjector(
                $this->repository,
                new CdbXmlDocumentFactory('3.3'),
                new AddressFactory(),
                new MetadataCdbItemEnricher(
                    new CdbXmlDateFormatter()
                )
            )
        )->withCdbXmlPublisher($this->cdbXmlPublisher);

        $this->metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
            ]
        );
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

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_imported_from_udb2()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $event = new OrganizerImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('actor-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage($id, $event);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_updated_from_udb2()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $event = new OrganizerUpdatedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('actor-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage($id, $event);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor.xml')
        );

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
     * @param string $fileName
     * @return string
     */
    private function loadCdbXmlFromFile($fileName)
    {
        return file_get_contents(__DIR__ . '/Repository/samples/' . $fileName);
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
