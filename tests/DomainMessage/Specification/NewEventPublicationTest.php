<?php

namespace CultuurNet\UDB3\CdbXmlService\DomainMessage\Specification;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;

class NewEventPublicationTest extends TestCase
{
    /**
     * @var SpecificationInterface
     */
    protected $specification;

    public function setUp(): void
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
                    new EventCreated(
                        '06887b21-9574-4b2f-848f-bc415956cf92',
                        new Language('nl'),
                        new Title('bla'),
                        new EventType('0.1', 'label'),
                        new LocationId('f79beab9-427f-43eb-bf7b-5c97bcde699f'),
                        new Calendar(CalendarType::PERMANENT())
                    ),
                    DateTime::now()
                ),
            ],
            [
                new DomainMessage(
                    UUID::generateAsString(),
                    0,
                    new Metadata([]),
                    new EventImportedFromUDB2(
                        '06887b21-9574-4b2f-848f-bc415956cf92',
                        '<cdbxml />',
                        'https://cdbxml-ns-uri'
                    ),
                    DateTime::now()
                ),
            ],
            [
                new DomainMessage(
                    UUID::generateAsString(),
                    0,
                    new Metadata([]),
                    new PlaceCreated(
                        '06887b21-9574-4b2f-848f-bc415956cf92',
                        new Language('nl'),
                        new Title('bla'),
                        new EventType('0.1', 'label'),
                        new Address(
                            new Street('street'),
                            new PostalCode('1000'),
                            new Locality('Brussels'),
                            Country::fromNative('BE')
                        ),
                        new Calendar(CalendarType::PERMANENT())
                    ),
                    DateTime::now()
                ),
            ],
            [
                new DomainMessage(
                    UUID::generateAsString(),
                    0,
                    new Metadata([]),
                    new PlaceImportedFromUDB2(
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
