<?php

use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\BroadwayAMQP\DomainMessageJSONDeserializer;
use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use DerAlex\Silex\YamlConfigServiceProvider;
use Silex\Application;
use ValueObjects\Number\Natural;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\String\String;

$app = new Application();

if (!isset($appConfigLocation)) {
    $appConfigLocation =  __DIR__;
}
$app->register(new YamlConfigServiceProvider($appConfigLocation . '/config.yml'));

$app['event_bus.udb3-core'] = $app->share(
    function (Application $app) {
        $bus =  new SimpleEventBus();

        // @todo Subscribe listeners.

        return $bus;
    }
);

/**
 * Turn debug on or off.
 */
$app['debug'] = $app['config']['debug'] === true;


$app['logger.amqp.event_bus_forwarder'] = $app->share(
    function (Application $app) {
        $logger = new Monolog\Logger('amqp.event_bus_forwarder');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

        $logFileHandler = new \Monolog\Handler\StreamHandler(
            __DIR__ . '/log/amqp.log',
            \Monolog\Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);

        return $logger;
    }
);

$app['deserializer_locator'] = $app->share(
    function (Application $app) {
        $deserializerLocator = new SimpleDeserializerLocator();
        $maps =
            \CultuurNet\UDB3\Event\Events\ContentTypes::MAP +
            \CultuurNet\UDB3\Place\Events\ContentTypes::MAP;

        foreach ($maps as $payloadClass => $contentType) {
            $deserializerLocator->registerDeserializer(
                new String($contentType),
                new DomainMessageJSONDeserializer($payloadClass)
            );
        }
        return $deserializerLocator;
    }
);

$app['event_bus_forwarding_consumer_factory'] = $app->share(
    function (Application $app) {
        return new EventBusForwardingConsumerFactory(
            Natural::fromNative($app['config']['consumerExecutionDelay']),
            $app['config']['amqp'],
            $app['logger.amqp.event_bus_forwarder'],
            $app['deserializer_locator'],
            $app['event_bus.udb3-core']
        );
    }
);

foreach (['udb3-core'] as $consumerId) {
    $app['amqp.' . $consumerId] = $app->share(
        function (Application $app) use ($consumerId) {
            $consumerConfig = $app['config']['amqp']['consumers'][$consumerId];
            $exchange = new StringLiteral($consumerConfig['exchange']);
            $queue = new StringLiteral($consumerConfig['queue']);

            /** @var EventBusForwardingConsumerFactory $consumerFactory */
            $consumerFactory = $app['event_bus_forwarding_consumer_factory'];

            $eventBusForwardingConsumer = $consumerFactory->create($exchange, $queue);

            return $eventBusForwardingConsumer->getConnection();
        }
    );
}

/**
 * Load additional bootstrap files.
 */
foreach ($app['config']['bootstrap'] as $identifier => $enabled) {
    if (true === $enabled) {
        require __DIR__ . "/bootstrap/{$identifier}.php";
    }
}

return $app;
