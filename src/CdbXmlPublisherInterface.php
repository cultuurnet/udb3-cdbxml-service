<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;

interface CdbXmlPublisherInterface
{
    /**
     * @param CdbXmlDocument $CDBXMLDocument
     * @param DomainMessage $domainMessage
     * @return mixed
     */
    public function publish(
        CdbXmlDocument $CDBXMLDocument,
        DomainMessage $domainMessage
    );
}
