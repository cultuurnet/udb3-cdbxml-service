<?php

use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\BroadwayAMQP\AMQPPublisher;
use CultuurNet\BroadwayAMQP\DomainMessageJSONDeserializer;
use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\OrganizerToActorCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\EventBusCdbXmlPublisher;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use DerAlex\Silex\YamlConfigServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Silex\Application;
use ValueObjects\Number\Natural;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\String\String;

$app = new Application();

if (!isset($appConfigLocation)) {
    $appConfigLocation =  __DIR__;
}
$app->register(new YamlConfigServiceProvider($appConfigLocation . '/config.yml'));

// Incoming event-stream from UDB3.
$app['event_bus.udb3-core'] = $app->share(
    function (Application $app) {
        $bus =  new SimpleEventBus();

        $bus->subscribe($app['organizer_to_actor_cdbxml_projector']);

        return $bus;
    }
);

// Outgoing event-stream going to UDB2.
$app['event_bus.udb2'] = $app->share(
    function (Application $app) {
        $bus =  new SimpleEventBus();

        $bus->subscribe($app['amqp.udb2_publisher']);

        return $bus;
    }
);

$app['organizer_to_actor_cdbxml_projector'] = $app->share(
    function (Application $app) {
        return (new OrganizerToActorCdbXmlProjector(
            $app['cdbxml_actor_repository'],
            $app['cdbxml_document_factory'],
            $app['address_factory']
        ))->withCdbXmlPublisher($app['cdbxml_publisher']);
    }
);

$app['cache'] = $app->share(
    function (Application $app) {
        $parameters = $app['config']['cache']['redis'];

        return function ($cachePrefix) use ($parameters) {
            return new Doctrine\Common\Cache\PredisCache(
                new Predis\Client(
                    $parameters,
                    [
                        'prefix' => $cachePrefix . '_',
                    ]
                )
            );
        };
    }
);

$app['cdbxml_actor_repository'] = $app->share(
    function (Application $app) {
        return new CacheDocumentRepository(
            $app['cdbxml_actor_cache']
        );
    }
);

$app['cdbxml_actor_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('cdbxml_actor');
    }
);

$app['cdbxml_document_factory'] = $app->share(
    function () {
        return new CdbXmlDocumentFactory('3.3');
    }
);

$app['address_factory'] = $app->share(
    function () {
        return new AddressFactory();
    }
);

$app['organizer_to_actor_cdbxml_projector'] = $app->share(
    function (Application $app) {
        return new OrganizerToActorCdbXmlProjector(
            $app['cdbxml_actor_repository'],
            $app['cdbxml_document_factory'],
            $app['address_factory']
        );
    }
);

$app['cache'] = $app->share(
    function (Application $app) {
        $parameters = $app['config']['cache']['redis'];

        return function ($cachePrefix) use ($parameters) {
            return new Doctrine\Common\Cache\PredisCache(
                new Predis\Client(
                    $parameters,
                    [
                        'prefix' => $cachePrefix . '_',
                    ]
                )
            );
        };
    }
);

$app['cdbxml_actor_repository'] = $app->share(
    function (Application $app) {
        return new CacheDocumentRepository(
            $app['cdbxml_actor_cache']
        );
    }
);

$app['cdbxml_actor_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('cdbxml_actor');
    }
);

$app['cdbxml_document_factory'] = $app->share(
    function () {
        return new CdbXmlDocumentFactory('3.3');
    }
);

$app['address_factory'] = $app->share(
    function () {
        return new AddressFactory();
    }
);

/**
 * Turn debug on or off.
 */
$app['debug'] = $app['config']['debug'] === true;

$app['document_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            // documents are typed and this should be clear in the iri
            // type and id are expected here, /-separated, eg: "event/B1BBDD85-4643-405E-852D-7D2D4D0E56BA"
            function ($typeAndCdbid) use ($app) {
                return $app['config']['url'] . $typeAndCdbid;
            }
        );
    }
);

$app['cdbxml_publisher'] = $app->share(
    function (Application $app) {
        return new EventBusCdbXmlPublisher(
            $app['document_iri_generator'],
            $app['event_bus.udb2']
        );
    }
);

$app['amqp.connection'] = $app->share(
    function (Application $app) {
        $amqpConfig = $host = $app['config']['amqp'];

        $connection = new AMQPStreamConnection(
            $amqpConfig['host'],
            $amqpConfig['port'],
            $amqpConfig['user'],
            $amqpConfig['password'],
            $amqpConfig['vhost']
        );

        return $connection;
    }
);

$app['amqp.udb2_publisher'] = $app->share(
    function (Application $app) {
        /** @var AMQPStreamConnection $connection */
        $connection = $app['amqp.connection'];
        $exchange = $app['config']['amqp']['publishers']['udb2']['exchange'];

        $map = [
            \CultuurNet\UDB2DomainEvents\EventCreated::class => 'application/vnd.cultuurnet.udb2-events.event-created+json',
            \CultuurNet\UDB2DomainEvents\EventUpdated::class => 'application/vnd.cultuurnet.udb2-events.event-updated+json',
            \CultuurNet\UDB2DomainEvents\ActorCreated::class => 'application/vnd.cultuurnet.udb2-events.actor-created+json',
        ];

        $classes = (new \CultuurNet\BroadwayAMQP\DomainMessage\SpecificationCollection());
        foreach (array_keys($map) as $className) {
            $classes = $classes->with(
                new \CultuurNet\BroadwayAMQP\DomainMessage\PayloadIsInstanceOf($className)
            );
        }

        $specification = new \CultuurNet\BroadwayAMQP\DomainMessage\AnyOf($classes);

        $contentTypeLookup = new \CultuurNet\BroadwayAMQP\ContentTypeLookup($map);

        $publisher = new AMQPPublisher(
            $connection->channel(),
            $exchange,
            $specification,
            $contentTypeLookup,
            new \CultuurNet\BroadwayAMQP\Message\EntireDomainMessageBodyFactory()
        );

        $publisher->setLogger($app['logger.amqp.udb2_publisher']);

        return $publisher;
    }
);

$app['logger.amqp.udb2_publisher'] = $app->share(
    function (Application $app) {
        $logger = new Monolog\Logger('amqp.udb2_publisher');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

        $logFileHandler = new \Monolog\Handler\StreamHandler(
            __DIR__ . '/log/amqp.log',
            \Monolog\Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);

        return $logger;
    }
);

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
            \CultuurNet\UDB3\Event\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Place\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Organizer\Events\ContentTypes::map();

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
            $app['amqp.connection'],
            $app['logger.amqp.event_bus_forwarder'],
            $app['deserializer_locator'],
            $app['event_bus.udb3-core'],
            new StringLiteral($app['config']['amqp']['consumer_tag'])
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

            $consumer = $consumerFactory->create($exchange, $queue);

            return $consumer->getConnection();
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
