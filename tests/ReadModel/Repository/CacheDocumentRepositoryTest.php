<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

class CacheDocumentRepositoryTest extends TestCase
{
    /**
     * @var ArrayCache
     */
    private $cache;

    /**
     * @var CacheDocumentRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
    }

    /**
     * @test
     */
    public function it_can_save_and_retrieve_cdbxml_documents()
    {
        $id = 1;
        $cdbXmlDocument = new CdbXmlDocument($id, '<cdb:foo></cdb:foo>');

        $this->repository->save($cdbXmlDocument);

        $this->assertEquals($cdbXmlDocument, $this->repository->get($id));
    }

    /**
     * @test
     */
    public function it_returns_null_if_no_cdbxml_document_can_be_found()
    {
        $this->assertNull($this->repository->get(999));
    }

    /**
     * @test
     */
    public function it_can_delete_a_cdbxml_document_and_throw_an_appropriate_exception_when_retrieving_it_later()
    {
        $id = 1;
        $cdbXmlDocument = new CdbXmlDocument($id, '<cdb:foo></cdb:foo>');

        $this->repository->save($cdbXmlDocument);
        $this->repository->remove($id);

        $this->expectException(DocumentGoneException::class);
        $this->repository->get($id);
    }
}
