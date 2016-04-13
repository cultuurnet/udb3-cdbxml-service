<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;

class NewPublication implements SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return in_array(
            get_class($domainMessage->getPayload()),
            [
                PlaceCreated::class,
                PlaceImportedFromUDB2::class,
                EventImportedFromUDB2::class,
                EventCreated::class,
            ]
        );
    }
}
