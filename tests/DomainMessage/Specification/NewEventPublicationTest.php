<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;

class NewEventPublicationTest extends AbstractSpecificationTest
{
    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function setUp()
    {
        $this->specification = new NewEventPublication();
    }

    /**
     * @test
     * @dataProvider domainMessages
     * @param DomainMessage $domainMessage
     */
    public function it_should_be_satisfied_by_domain_messages_that_create_a_new_event_publication(
        DomainMessage $domainMessage
    ) {
        $this->assertTrue($this->specification->isSatisfiedBy($domainMessage));
    }

    public function domainMessages()
    {
        return [
            [$this->createDomainMessageForEventClass(EventCreated::class)],
            [$this->createDomainMessageForEventClass(EventImportedFromUDB2::class)],
            [$this->createDomainMessageForEventClass(PlaceCreated::class)],
            [$this->createDomainMessageForEventClass(PlaceImportedFromUDB2::class)],
        ];
    }
}
