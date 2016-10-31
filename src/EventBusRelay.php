<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EventBusRelay implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var string[]
     */
    private $payloadClassNames;

    /**
     * EventBusRelay constructor.
     * @param EventBusInterface $eventBus
     * @param array $payloadClassNames
     */
    public function __construct(
        EventBusInterface $eventBus,
        array $payloadClassNames
    ) {
        $this->eventBus = $eventBus;
        $this->payloadClassNames = $payloadClassNames;
        $this->logger = new NullLogger();
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        if (in_array($payloadClassName, $this->payloadClassNames)) {
            $this->logger->info('Relaying message ' . $payloadClassName);

            $this->eventBus->publish(new DomainEventStream([$domainMessage]));
        }
    }
}
