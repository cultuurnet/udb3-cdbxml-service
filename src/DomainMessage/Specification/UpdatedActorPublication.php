<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;

class UpdatedActorPublication extends AbstractPayloadTypeSpecification
{
    /**
     * @return string[]
     */
    protected function validClassNames()
    {
        return [
            OrganizerUpdatedFromUDB2::class,
        ];
    }
}
