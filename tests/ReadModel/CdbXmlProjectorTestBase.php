<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use Doctrine\Common\Cache\ArrayCache;

abstract class CdbXmlProjectorTestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventListenerInterface
     */
    protected $projector;

    /**
     * @var string
     */
    private $cdbXmlFilesPath;

    /**
     * @var ArrayCache
     */
    protected $cache;

    /**
     * @var CacheDocumentRepository
     */
    protected $repository;

    /**
     * @var CdbXmlPublisherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cdbXmlPublisher;

    /**
     * @var CdbXmlDocument[]
     */
    private $publishedCdbXmlDocuments;

    public function setUp()
    {
        $this->cdbXmlFilesPath = __DIR__;

        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $this->cdbXmlPublisher = $this->getMock(CdbXmlPublisherInterface::class);

        $this->cdbXmlPublisher->expects($this->any())
            ->method('publish')
            ->willReturnCallback(
                function (CdbXmlDocument $document, DomainMessage $domainMessage) {
                    $this->publishedCdbXmlDocuments[] = $document;
                }
            );
    }

    /**
     * @param string $cdbXmlFilesPath
     */
    protected function setCdbXmlFilesPath($cdbXmlFilesPath)
    {
        $cdbXmlFilesPath = (string) $cdbXmlFilesPath;
        $this->cdbXmlFilesPath = rtrim($cdbXmlFilesPath, '/');
    }

    /**
     * @param string $entityId
     * @param object $event
     * @param Metadata|null $metadata
     * @param DateTime $dateTime
     * @return DomainMessage
     */
    protected function createDomainMessage(
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
     * @param string $id
     * @param array $events
     * @param Metadata $metadata
     * @return DomainEventStream
     */
    protected function createDomainEventStream(
        $id,
        array $events,
        Metadata $metadata
    ) {
        $domainMessages = [];
        foreach ($events as $event) {
            $domainMessages[] = $this->createDomainMessage($id, $event, $metadata);
        }
        return new DomainEventStream($domainMessages);
    }

    /**
     * @param DomainEventStream $stream
     */
    protected function handleDomainEventStream(DomainEventStream $stream)
    {
        foreach ($stream as $message) {
            $this->projector->handle($message);
        }
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function loadCdbXmlFromFile($fileName)
    {
        return file_get_contents($this->cdbXmlFilesPath . '/' . $fileName);
    }

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     */
    protected function assertCdbXmlDocumentIsPublished(CdbXmlDocument $cdbXmlDocument)
    {
        $this->assertTrue(in_array($cdbXmlDocument, $this->publishedCdbXmlDocuments));
    }

    /**
     * @param CdbXmlDocument[] $cdbXmlDocuments
     */
    protected function assertCdbXmlDocumentsArePublished(array $cdbXmlDocuments)
    {
        // Filter out any published cdbxml documents that do not need to be
        // asserted. (Eg. CdbXml documents published when setting up the test.)
        $published = array_filter(
            $this->publishedCdbXmlDocuments,
            function (CdbXmlDocument $document) use ($cdbXmlDocuments) {
                return in_array($document, $cdbXmlDocuments);
            }
        );

        $published = array_values($published);

        $this->assertEquals($published, $cdbXmlDocuments);
    }

    /**
     * @param CdbXmlDocument $expectedCdbXmlDocument
     */
    protected function assertCdbXmlDocumentInRepository(CdbXmlDocument $expectedCdbXmlDocument)
    {
        $cdbId = $expectedCdbXmlDocument->getId();
        $actualCdbXmlDocument = $this->repository->get($cdbId);

        if (is_null($actualCdbXmlDocument)) {
            $this->fail("CdbXmlDocument for CdbId {$cdbId} not found.");
        }

        $this->assertEquals($expectedCdbXmlDocument->getCdbXml(), $actualCdbXmlDocument->getCdbXml());
    }

    /**
     * @param CdbXmlDocument[] $cdbXmlDocuments
     */
    protected function assertFinalCdbXmlDocumentInRepository(array $cdbXmlDocuments)
    {
        $final = $cdbXmlDocuments[count($cdbXmlDocuments) - 1];
        $this->assertCdbXmlDocumentInRepository($final);
    }
}
