<?php

use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\UDB2\AMQP\EventBusForwardingConsumer;
use DerAlex\Silex\YamlConfigServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Silex\Application;
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

foreach (['udb3-core'] as $consumerId) {
    $app['amqp.' . $consumerId] = $app->share(
        function (Application $app) use ($consumerId) {
            $consumerConfig = $app['config']['amqp']['consumers'][$consumerId];
            $amqpConfig = $host = $app['config']['amqp'];
            $connection = new AMQPStreamConnection(
                $amqpConfig['host'],
                $amqpConfig['port'],
                $amqpConfig['user'],
                $amqpConfig['password'],
                $amqpConfig['vhost']
            );

            $deserializerLocator = new SimpleDeserializerLocator();
            $deserializerLocator->registerDeserializer(
                new String(
                    'application/vnd.cultuurnet.udb2-events.event-created+json'
                ),
                new \CultuurNet\UDB2DomainEvents\EventCreatedJSONDeserializer()
            );
            $deserializerLocator->registerDeserializer(
                new String(
                    'application/vnd.cultuurnet.udb2-events.event-updated+json'
                ),
                new \CultuurNet\UDB2DomainEvents\EventUpdatedJSONDeserializer()
            );

            $eventBusForwardingConsumer = new EventBusForwardingConsumer(
                $connection,
                $app['event_bus.' . $consumerId],
                $deserializerLocator,
                new String($amqpConfig['consumer_tag']),
                new String($consumerConfig['exchange']),
                new String($consumerConfig['queue'])
            );

            $logger = new Monolog\Logger('amqp.udb3-core');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));
            $eventBusForwardingConsumer->setLogger($logger);

            return $connection;
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
