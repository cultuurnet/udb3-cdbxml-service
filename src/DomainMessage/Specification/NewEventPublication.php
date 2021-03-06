<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;

class NewEventPublication extends AbstractPayloadTypeSpecification
{
    /**
     * @return string[]
     */
    protected function validClassNames()
    {
        return [
            PlaceCreated::class,
            PlaceImportedFromUDB2::class,
            EventImportedFromUDB2::class,
            EventCreated::class,
        ];
    }
}
