<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\CdbXmlService\Samples\AbstractDummyEvent;
use CultuurNet\UDB3\CdbXmlService\Samples\DummyAddedEvent;
use CultuurNet\UDB3\CdbXmlService\Samples\DummyCreatedEvent;
use CultuurNet\UDB3\CdbXmlService\Samples\DummyRemovedEvent;
use ValueObjects\Identity\UUID;

class EventBusRelayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var EventBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventBus;

    /**
     * @var EventBusRelay
     */
    private $eventBusRelay;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->eventBus = $this->getMock(EventBusInterface::class);

        $this->eventBusRelay = new EventBusRelay(
            $this->eventBus,
            [
                DummyAddedEvent::class,
                DummyRemovedEvent::class,
            ]
        );
    }

    /**
     * @test
     */
    public function it_handles_a_known_event()
    {
        $domain = $this->createDomainMessage(
            new DummyAddedEvent($this->uuid->toNative())
        );

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->eventBusRelay->handle($domain);
    }

    /**
     * @test
     */
    public function it_does_not_handle_an_unknown_event()
    {
        $domain = $this->createDomainMessage(
            new DummyCreatedEvent($this->uuid->toNative())
        );

        $this->eventBus->expects($this->never())
            ->method('publish');

        $this->eventBusRelay->handle($domain);
    }

    /**
     * @param AbstractDummyEvent $dummyEvent
     * @return DomainMessage
     */
    private function createDomainMessage(AbstractDummyEvent $dummyEvent)
    {
        return new DomainMessage(
            $this->uuid,
            0,
            new Metadata(),
            $dummyEvent,
            BroadwayDateTime::now()
        );
    }
}
