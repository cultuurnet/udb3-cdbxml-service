<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use Doctrine\Common\Cache\ArrayCache;

abstract class CdbXmlProjectorTestBase extends \PHPUnit_Framework_TestCase
{
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

    public function setUp()
    {
        $this->cdbXmlFilesPath = __DIR__;

        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $this->cdbXmlPublisher = $this->getMock(CdbXmlPublisherInterface::class);
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
     * @param string $fileName
     * @return string
     */
    protected function loadCdbXmlFromFile($fileName)
    {
        return file_get_contents($this->cdbXmlFilesPath . '/' . $fileName);
    }

    /**
     * @param \CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument $expectedCdbXmlDocument
     * @param DomainMessage $domainMessage
     */
    protected function expectCdbXmlDocumentToBePublished(
        CdbXmlDocument $expectedCdbXmlDocument,
        DomainMessage $domainMessage
    ) {
        $this->cdbXmlPublisher->expects($this->once())
            ->method('publish')
            ->with($expectedCdbXmlDocument, $domainMessage);
    }

    /**
     * @param \CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument $expectedCdbXmlDocument
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
}
