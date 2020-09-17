<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class NewActorPublicationTest extends TestCase
{
    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function setUp(): void
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
    ): void {
        $this->assertTrue($this->specification->isSatisfiedBy($domainMessage));
    }

    public function domainMessages(): array
    {
        return [
            [
                new DomainMessage(
                    UUID::generateAsString(),
                    0,
                    new Metadata([]),
                    new OrganizerCreated(
                        '06887b21-9574-4b2f-848f-bc415956cf92',
                        new Title('bla'),
                        [],
                        [],
                        [],
                        []
                    ),
                    DateTime::now()
                ),
            ],
            [
                new DomainMessage(
                    UUID::generateAsString(),
                    0,
                    new Metadata([]),
                    new OrganizerImportedFromUDB2(
                        '06887b21-9574-4b2f-848f-bc415956cf92',
                        '<cdbxml />',
                        'https://cdbxml-ns-uri'
                    ),
                    DateTime::now()
                ),
            ],
        ];
    }
}
