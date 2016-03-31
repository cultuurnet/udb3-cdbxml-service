<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;

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
     * @param CdbXmlDocument $readModel
     */
    public function save(CdbXmlDocument $readModel);

    /**
     * @param string $id
     */
    public function remove($id);
}

