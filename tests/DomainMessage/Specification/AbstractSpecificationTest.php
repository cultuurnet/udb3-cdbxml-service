<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use ValueObjects\Identity\UUID;

abstract class AbstractSpecificationTest extends \PHPUnit_Framework_TestCase
{
    protected function createDomainMessageForEventClass($eventClass)
    {
        return new DomainMessage(
            UUID::generateAsString(),
            0,
            new Metadata([]),
            $this->getMock($eventClass, [], [], $eventClass, false),
            DateTime::now()
        );
    }
}