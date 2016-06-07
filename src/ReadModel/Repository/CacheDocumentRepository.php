<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
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
     * @return CdbXmlDocument|null
     *
     * @throws DocumentGoneException
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

        return new CdbXmlDocument($id, $value);
    }

    /**
     * @param CdbXmlDocument $document
     */
    public function save(CdbXmlDocument $document)
    {
        $this->cache->save(
            $document->getId(),
            $document->getCdbXml(),
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
