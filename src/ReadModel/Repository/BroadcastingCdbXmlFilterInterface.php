<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

interface BroadcastingCdbXmlFilterInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return boolean
     */
    public function matches(CdbXmlDocument $cdbXmlDocument);
}
