<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\SpecificationInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use ValueObjects\Identity\UUID;

class NewPublicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function setUp()
    {
        $this->specification = new NewPublication();
    }

    /**
     * @test
     * @dataProvider domainMessages
     */
    public function it_should_be_satisfied_by_domain_messages_that_create_a_new_publication(
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

    private function createDomainMessageForEventClass($eventClass)
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
