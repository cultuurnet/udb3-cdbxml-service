<?php

namespace CultuurNet\UDB3\CDBXMLService\ReadModel\Repository;

use CultuurNet\UDB3\CDBXMLService\ReadModel\Repository\CDBXMLDocument;
use CultuurNet\UDB3\CDBXMLService\ReadModel\Repository\DocumentGoneException;

interface DocumentRepositoryInterface
{
    /**
     * @param string $id
     * @return CDBXMLDocument|null
     *
     * @throws DocumentGoneException
     */
    public function get($id);

    /**
     * @param CDBXMLDocument $readModel
     */
    public function save(CDBXMLDocument $readModel);

    /**
     * @param string $id
     */
    public function remove($id);
}

