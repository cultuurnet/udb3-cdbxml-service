<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use ValueObjects\Identity\UUID;
use PHPUnit\Framework\TestCase;

abstract class AbstractSpecificationTest extends TestCase
{
    protected function createDomainMessageForEventClass($eventClass)
    {
        $mockBuilder = $this->getMockBuilder($eventClass);
        $mockBuilder->disableOriginalConstructor();
        $mockBuilder->disableProxyingToOriginalMethods();

        return new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata([]),
            $mockBuilder->getMock(),
            DateTime::now()
        );
    }
}
