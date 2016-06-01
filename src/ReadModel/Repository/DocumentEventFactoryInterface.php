<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

interface DocumentEventFactoryInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return AbstractEvent
     */
    public function createEvent(CdbXmlDocument $cdbXmlDocument);
}
