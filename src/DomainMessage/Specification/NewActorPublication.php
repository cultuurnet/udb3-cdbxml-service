<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;

class NewActorPublication extends AbstractPayloadTypeSpecification
{
    /**
     * @return string[]
     */
    protected function validClassNames()
    {
        return [
            OrganizerCreated::class,
            OrganizerImportedFromUDB2::class,
        ];
    }
}
