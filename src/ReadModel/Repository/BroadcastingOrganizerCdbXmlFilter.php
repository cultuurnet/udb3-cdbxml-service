<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

class BroadcastingOrganizerCdbXmlFilter implements BroadcastingCdbXmlFilterInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return boolean
     */
    public function matches(CdbXmlDocument $cdbXmlDocument)
    {
        $matches = true;

        return $matches;
    }
}
