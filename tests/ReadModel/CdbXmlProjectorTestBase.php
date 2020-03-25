<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

abstract class CdbXmlProjectorTestBase extends TestCase
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

    public function setUp()
    {
        $this->cdbXmlFilesPath = __DIR__;

        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
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
     * @param string $id
     * @param string $fileName
     * @return \CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument
     */
    protected function loadCdbXmlDocumentFromFile(string $id, string $fileName)
    {
        return new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile($fileName)
        );
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
