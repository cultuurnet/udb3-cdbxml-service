<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

interface BroadcastingCdbXmlFilterInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return boolean
     */
    public function matches(CdbXmlDocument $cdbXmlDocument);
}
