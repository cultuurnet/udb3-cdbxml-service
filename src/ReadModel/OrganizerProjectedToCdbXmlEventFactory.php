<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentEventFactoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;

class OrganizerProjectedToCdbXmlEventFactory implements DocumentEventFactoryInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @param bool $isNew
     * @return OrganizerEvent
     */
    public function createEvent(CdbXmlDocument $cdbXmlDocument, $isNew = false)
    {
        return new OrganizerProjectedToCdbXml(
            $cdbXmlDocument->getId()
        );
    }
}
