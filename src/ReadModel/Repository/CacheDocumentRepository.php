<?php

namespace CultuurNet\UDB3\CDBXMLService\ReadModel\Repository;

use Doctrine\Common\Cache\Cache;

class CacheDocumentRepository implements DocumentRepositoryInterface
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $id
     * @return CDBXMLDocument|null
     */
    public function get($id)
    {
        $value = $this->cache->fetch($id);

        if ('GONE' === $value) {
            throw new DocumentGoneException();
        }

        if (false === $value) {
            return null;
        }

        return new CDBXMLDocument($id, $value);
    }

    /**
     * @param CDBXMLDocument $document
     */
    public function save(CDBXMLDocument $document)
    {
        $this->cache->save(
            $document->getId(),
            $document->getCDBXML(),
            0
        );
    }

    /**
     * @param string $id
     */
    public function remove($id)
    {
        $this->cache->save($id, 'GONE', 0);
    }
}
