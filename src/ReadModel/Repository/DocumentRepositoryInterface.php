<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

interface DocumentRepositoryInterface
{
    /**
     * @param string $id
     * @return CdbXmlDocument|null
     *
     * @throws DocumentGoneException
     */
    public function get($id);

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     */
    public function save(CdbXmlDocument $cdbXmlDocument);

    /**
     * @param string $id
     */
    public function remove($id);
}
