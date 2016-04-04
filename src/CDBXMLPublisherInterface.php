<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;

interface CdbXmlPublisherInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @param DomainMessage $domainMessage
     * @return mixed
     */
    public function publish(
        CdbXmlDocument $cdbXmlDocument,
        DomainMessage $domainMessage
    );
}
