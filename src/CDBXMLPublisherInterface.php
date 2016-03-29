<?php

namespace CultuurNet\UDB3\CDBXMLService;

use Broadway\Domain\DomainMessage;

interface CDBXMLPublisherInterface
{
    /**
     * @param CDBXMLDocument $CDBXMLDocument
     * @param DomainMessage $domainMessage
     * @return mixed
     */
    public function publish(
        CDBXMLDocument $CDBXMLDocument,
        DomainMessage $domainMessage
    );
}
