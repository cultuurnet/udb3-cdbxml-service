<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;

class NewActorPublicationTest extends AbstractSpecificationTest
{
    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function setUp()
    {
        $this->specification = new NewActorPublication();
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
            [$this->createDomainMessageForEventClass(OrganizerCreated::class)],
            [$this->createDomainMessageForEventClass(OrganizerImportedFromUDB2::class)],
        ];
    }
}
