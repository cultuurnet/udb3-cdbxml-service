<?php

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\BroadwayAMQP\AMQPPublisher;
use CultuurNet\BroadwayAMQP\DomainMessageJSONDeserializer;
use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\BroadwayAMQP\Message\Body\BodyFactoryInterface;
use CultuurNet\BroadwayAMQP\Message\Body\PayloadOnlyBodyFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\Geocoding\CachedGeocodingService;
use CultuurNet\Geocoding\DefaultGeocodingService;
use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB2DomainEvents\ActorUpdated;
use CultuurNet\UDB2DomainEvents\EventCreated;
use CultuurNet\UDB2DomainEvents\EventUpdated;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocumentController;
use CultuurNet\UDB3\CdbXmlService\DatabaseSchemaInstaller;
use CultuurNet\UDB3\CdbXmlService\EventBusRelay;
use CultuurNet\UDB3\CdbXmlService\Labels\UitpasLabelApplier;
use CultuurNet\UDB3\CdbXmlService\Labels\UitpasLabelFilter;
use CultuurNet\UDB3\CdbXmlService\Labels\UitpasLabelProvider;
use CultuurNet\UDB3\CdbXmlService\ReadModel\CdbXmlDateFormatter;
use CultuurNet\UDB3\CdbXmlService\ReadModel\GeocodingOfferCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\MetadataCdbItemEnricher;
use CultuurNet\UDB3\CdbXmlService\ReadModel\FlandersRegionOrganizerCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\FlandersRegionCategoryService;
use CultuurNet\UDB3\CdbXmlService\ReadModel\FlandersRegionOfferCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\FlandersRegionRelationsCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\OfferToCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\OrganizerToActorCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\RelationsToCdbXmlProjector;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\EventBusCdbXmlPublisher;
use CultuurNet\UDB3\Label\LabelEventRelationTypeResolver;
use CultuurNet\UDB3\SimpleEventBus as UDB3SimpleEventBus;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\NewlineToBreakTagStringFilter;
use CultuurNet\UDB3\StringFilter\NewlineToSpaceStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;
use DerAlex\Silex\YamlConfigServiceProvider;
use Geocoder\Provider\GoogleMapsProvider;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Symfony\Component\Yaml\Yaml;
use ValueObjects\Number\Natural;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\String\String;
use ValueObjects\Web\Url;

date_default_timezone_set('Europe/Brussels');

const CDBXML_OFFER_REPOSITORY = 'cdbxml_offer_repository';
const CDBXML_DOCUMENT_FACTORY = 'cdbxml_document_factory';
const OFFER_LABEL_RELATION_REPOSITORY= 'offer_label_relation_repository';

$app = new Application();

if (!isset($appConfigLocation)) {
    $appConfigLocation =  __DIR__;
}
$app->register(new YamlConfigServiceProvider($appConfigLocation . '/config.yml'));

// Incoming event-stream from UDB3.
$app['event_bus.udb3-core'] = $app->share(
    function (Application $app) {
        $bus =  new UDB3SimpleEventBus();

        $bus->subscribe($app['labels_relations_projector']);
        $bus->subscribe($app['organizer_to_actor_cdbxml_projector']);
        $bus->subscribe($app['offer_to_event_cdbxml_projector']);
        $bus->subscribe($app['label_to_item_cdbxml_projector']);
        $bus->subscribe($app['event_relations_projector']);
        $bus->subscribe($app['place_relations_projector']);
        $bus->subscribe($app['flanders_region_actor_cdbxml_projector']);
        $bus->subscribe($app['flanders_region_offer_cdbxml_projector']);
        $bus->subscribe($app['geocoding_offer_cdbxml_projector']);

        $bus->beforeFirstPublication(function (EventBusInterface $bus) use ($app) {
            $bus->subscribe($app['organizer_event_bus_relay']);
        });

        return $bus;
    }
);

// Broadcasting event stream to trigger updating of related projections.
$app['event_bus.udb3-core.relations'] = $app->share(
    function (Application $app) {
        $bus = new UDB3SimpleEventBus();

        $bus->beforeFirstPublication(function (EventBusInterface $bus) use ($app) {
            $bus->subscribe($app['relations_to_cdbxml_projector']);
            $bus->subscribe($app['flanders_region_relations_cdbxml_projector']);
            $bus->subscribe($app['cdbxml_publisher']);
        });

        return $bus;
    }
);

$app['organizer_event_bus_relay'] = $app->share(
    function (Application $app) {
        $eventBusRelay = new EventBusRelay(
            $app['event_bus.udb3-core.relations'],
            [
                \CultuurNet\UDB3\Organizer\Events\LabelAdded::class,
                \CultuurNet\UDB3\Organizer\Events\LabelRemoved::class
            ]
        );

        $eventBusRelay->setLogger($app['logger.projector']);

        return $eventBusRelay;
    }
);

// Outgoing event-stream to UDB2.
$app['event_bus.udb2'] = $app->share(
    function (Application $app) {
        $bus =  new SimpleEventBus();

        $bus->subscribe($app['amqp.publisher']);

        return $bus;
    }
);

$app['organizer_to_actor_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = (new OrganizerToActorCdbXmlProjector(
            $app['cdbxml_actor_repository'],
            $app[CDBXML_DOCUMENT_FACTORY],
            $app['address_factory'],
            $app['metadata_cdb_item_enricher']
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['uitpas_label_filter'] = $app->share(
    function (Application $app) {
        $uitpasLabelProvider = new UitpasLabelProvider(
            new \Guzzle\Http\Client(),
            Url::fromNative($app['config']['uitpas_service']['labels_url'])
        );

        return new UitpasLabelFilter($uitpasLabelProvider);
    }
);

$app['uitpas_label_applier'] = $app->share(
    function (Application $app) {
        return new UitpasLabelApplier($app['uitpas_label_filter']);
    }
);

$app['offer_to_event_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $longDescriptionFilter = new NewlineToBreakTagStringFilter();

        $truncateFilter = new TruncateStringFilter(400);
        $truncateFilter->addEllipsis();
        $truncateFilter->turnOnWordSafe();

        $shortDescriptionFilter = new CombinedStringFilter();
        $shortDescriptionFilter->addFilter(new NewlineToSpaceStringFilter());
        $shortDescriptionFilter->addFilter($truncateFilter);

        $projector = (new OfferToCdbXmlProjector(
            $app[CDBXML_OFFER_REPOSITORY],
            $app[CDBXML_DOCUMENT_FACTORY],
            $app['metadata_cdb_item_enricher'],
            $app['cdbxml_actor_repository'],
            $app['cdbxml_date_formatter'],
            $app['address_factory'],
            $longDescriptionFilter,
            $shortDescriptionFilter,
            new \CommerceGuys\Intl\Currency\CurrencyRepository(),
            new \CommerceGuys\Intl\NumberFormat\NumberFormatRepository(),
            $app['event_cdbid_extractor'],
            $app['uitpas_label_applier']
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['label_to_item_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = (new \CultuurNet\UDB3\CdbXmlService\ReadModel\LabelToItemCdbxmlProjector(
            $app[CDBXML_OFFER_REPOSITORY],
            $app[OFFER_LABEL_RELATION_REPOSITORY],
            $app[CDBXML_DOCUMENT_FACTORY]
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['offer_relations_service'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsService(
            $app['event_relations_repository'],
            $app['place_relations_repository']
        );
    }
);

$app['iri_offer_identifier_factory'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\Offer\IriOfferIdentifierFactory(
            $app['config']['offer_url_regex']
        );
    }
);

$app['relations_to_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = (new RelationsToCdbXmlProjector(
            $app['cdbxml_offer_repository'],
            $app[CDBXML_DOCUMENT_FACTORY],
            $app['metadata_cdb_item_enricher'],
            $app['cdbxml_actor_repository'],
            $app['offer_relations_service'],
            $app['iri_offer_identifier_factory'],
            $app['uitpas_label_filter'],
            $app['uitpas_label_applier']
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['flanders_region_categories'] = $app->share(
    function (Application $app) {

        if (file_exists('config/term.xml')) {
            $xml = file_get_contents('config/term.xml');
        }
        else {
            $xml = '<cdbxml></cdbxml>';
        }
        $categories = new FlandersRegionCategoryService($xml);
        return $categories;
    }
);



$app['flanders_region_actor_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = (new FlandersRegionOrganizerCdbXmlProjector(
            $app['cdbxml_actor_repository'],
            $app['cdbxml_document_factory'],
            $app['flanders_region_categories']
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['flanders_region_offer_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = (new FlandersRegionOfferCdbXmlProjector(
            $app['cdbxml_offer_repository'],
            $app['cdbxml_document_factory'],
            $app['flanders_region_categories']
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['flanders_region_relations_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = new FlandersRegionRelationsCdbXmlProjector(
            $app['real_cdbxml_offer_repository'],
            $app['cdbxml_document_factory'],
            $app['flanders_region_categories'],
            $app['offer_relations_service'],
            $app['iri_offer_identifier_factory']
        );

        $projector->setLogger($app['logger.projector']);

        return $projector;
    }
);

$app['geocoding_service'] = $app->share(
    function (Application $app) {
        return new DefaultGeocodingService(
            new Geocoder\Geocoder(
                new GoogleMapsProvider(
                    new Geocoder\HttpAdapter\CurlHttpAdapter(),
                    null,
                    null,
                    false,
                    isset($app['config']['google_maps_api_key']) ? $app['config']['google_maps_api_key'] : null
                )
            )
        );
    }
);

$app['cached_geocoding_service'] = $app->share(
    function (Application $app) {
        return new CachedGeocodingService(
            $app['geocoding_service'],
            $app['cache']('geocoords')
        );
    }
);

$app['geocoding_offer_cdbxml_projector'] = $app->share(
    function (Application $app) {
        $projector = (new GeocodingOfferCdbXmlProjector(
            $app['real_cdbxml_offer_repository'],
            $app['cdbxml_document_factory'],
            $app['offer_relations_service'],
            new DefaultAddressFormatter(),
            $app['cached_geocoding_service']
        ));

        $projector->setLogger($app['logger.projector']);

        return $projector;
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

$app['real_cdbxml_actor_repository'] = $app->share(
    function (Application $app) {
        return new CacheDocumentRepository(
            $app['cdbxml_actor_cache']
        );
    }
);

$app['cdbxml_document_parser'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser();
    }
);

$app['cdbxml_actor_repository'] = $app->share(
    function (Application $app) {
        $broadcastingRepository = new BroadcastingDocumentRepositoryDecorator(
            $app['real_cdbxml_actor_repository'],
            $app['event_bus.udb3-core.relations'],
            new \CultuurNet\UDB3\CdbXmlService\ReadModel\OrganizerProjectedToCdbXmlEventFactory(),
            $app['cdbxml_offer_metadata_factory']
        );

        return $broadcastingRepository;
    }
);

$app['cdbxml_actor_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('cdbxml_actor');
    }
);

$app['real_cdbxml_offer_repository'] = $app->share(
    function (Application $app) {
        return new CacheDocumentRepository(
            $app['cdbxml_offer_cache']
        );
    }
);

$app[CDBXML_OFFER_REPOSITORY] = $app->share(
    function (Application $app) {
        $broadcastingRepository = new BroadcastingDocumentRepositoryDecorator(
            $app['real_cdbxml_offer_repository'],
            $app['event_bus.udb3-core.relations'],
            new \CultuurNet\UDB3\CdbXmlService\ReadModel\OfferProjectedToCdbXmlEventFactory(
                $app['document_iri_generator'],
                $app['cdbxml_document_parser']
            ),
            $app['cdbxml_offer_metadata_factory']
        );

        return $broadcastingRepository;
    }
);

$app['cdbxml_offer_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('cdbxml_offer');
    }
);

$app['cdbxml_offer_metadata_factory'] = $app->share(
    function () {
        return new \CultuurNet\UDB3\CdbXmlService\ReadModel\OfferDocumentMetadataFactory();
    }
);

$app[CDBXML_DOCUMENT_FACTORY] = $app->share(
    function () {
        return new CdbXmlDocumentFactory('3.3');
    }
);

$app['address_factory'] = $app->share(
    function () {
        return new AddressFactory();
    }
);

$app['cdbxml_date_formatter'] = $app->share(
    function () {
        return new CdbXmlDateFormatter();
    }
);

$app['metadata_cdb_item_enricher'] = $app->share(
    function (Application $app) {
        return new MetadataCdbItemEnricher(
            $app['cdbxml_date_formatter']
        );
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
                $baseUrl = rtrim($app['config']['url'], '/');
                $typeAndCdbid = trim($typeAndCdbid, '/');
                return $baseUrl . '/' . $typeAndCdbid;
            }
        );
    }
);

$app['cdbxml_publisher'] = $app->share(
    function (Application $app) {
        $publisher = new EventBusCdbXmlPublisher(
            $app['event_bus.udb2'],
            $app['iri_offer_identifier_factory']
        );

        $publisher->setLogger($app['logger.amqp.udb2_publisher']);

        return $publisher;
    }
);

$app->register(
    new \CultuurNet\SilexAMQP\AMQPConnectionServiceProvider(),
    [
        'amqp.connection.host' => $app['config']['amqp']['host'],
        'amqp.connection.port' => $app['config']['amqp']['port'],
        'amqp.connection.user' => $app['config']['amqp']['user'],
        'amqp.connection.password' => $app['config']['amqp']['password'],
        'amqp.connection.vhost' => $app['config']['amqp']['vhost'],
    ]
);

$app->register(
    new \CultuurNet\SilexAMQP\AMQPPublisherServiceProvider(),
    [
        'amqp.publisher.content_type_map' => [
            EventCreated::class => 'application/vnd.cultuurnet.udb2-events.event-created+json',
            EventUpdated::class => 'application/vnd.cultuurnet.udb2-events.event-updated+json',
            ActorCreated::class => 'application/vnd.cultuurnet.udb2-events.actor-created+json',
            ActorUpdated::class => 'application/vnd.cultuurnet.udb2-events.actor-updated+json',
        ],
        'amqp.publisher.exchange_name' => $app['config']['amqp']['publishers']['udb2']['exchange'],
    ]
);

$app->extend('amqp.publisher.body_factory', function (BodyFactoryInterface $originalBodyFactory) {
    return new PayloadOnlyBodyFactory();
});

$app->extend(
    'amqp.publisher',
    function (AMQPPublisher $amqpPublisher, Application $app) {
        $amqpPublisher->setLogger($app['logger.amqp.udb2_publisher']);
        return $amqpPublisher;
    }
);

$app['logger.amqp.udb2_publisher'] = $app->share(
    function (Application $app) {
        $logger = new Monolog\Logger('amqp.udb2_publisher');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $logFileHandler = new StreamHandler(
            __DIR__ . '/log/amqp.log',
            \Monolog\Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);

        return $logger;
    }
);

$app['logger.projector'] = $app->share(
    function (Application $app) {
        $logger = new Monolog\Logger('projector');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $logFileHandler = new StreamHandler(
            __DIR__ . '/log/projector.log',
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
            \CultuurNet\UDB3\Label\Events\ContentTypes::map() +
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
            $app['logger.amqp.udb2_publisher'],
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

            return $consumerFactory->create($exchange, $queue);
        }
    );
}

$app['cdbxml_offer.controller'] = $app->share(
    function (Application $app) {
        return new CdbXmlDocumentController(
            $app['real_cdbxml_offer_repository']
        );
    }
);

$app['cdbxml_actor.controller'] = $app->share(
    function (Application $app) {
        return new CdbXmlDocumentController(
            $app['real_cdbxml_actor_repository']
        );
    }
);

$app['dbal_connection'] = $app->share(
    function ($app) {
        $eventManager = new \Doctrine\Common\EventManager();
        $sqlMode = 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES';
        $query = "SET SESSION sql_mode = '{$sqlMode}'";
        $eventManager->addEventSubscriber(
            new \Doctrine\DBAL\Event\Listeners\SQLSessionInit($query)
        );

        $connection = \Doctrine\DBAL\DriverManager::getConnection(
            $app['config']['database'],
            null,
            $eventManager
        );

        return $connection;
    }
);

$app['dbal_connection:keepalive'] = $app->protect(
    function (Application $app) {
        /** @var \Doctrine\DBAL\Connection $db */
        $db = $app['dbal_connection'];

        $db->query('SELECT 1')->execute();
    }
);

$app['event_relations_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository(
            $app['dbal_connection']
        );
    }
);

$app['place_relations_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine\DBALRepository(
            $app['dbal_connection']
        );
    }
);

$app['labels_relations_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALWriteRepository(
            $app['dbal_connection'],
            new StringLiteral('labels_relations')
        );
    }
);

$app[OFFER_LABEL_RELATION_REPOSITORY] = $app->share(
  function (Application $app) {
      return new \CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository(
          $app['dbal_connection'],
          new StringLiteral('labels_relations')
      );
  }
);

$app['event_relations_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Relations\Projector(
            $app['event_relations_repository'],
            $app['event_cdbid_extractor']
        );
    }
);

$app['place_relations_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Place\ReadModel\Relations\Projector(
            $app['place_relations_repository']
        );
    }
);

$app['labels_relations_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Label\ReadModels\Relations\Projector(
            $app['labels_relations_repository'],
            new LabelEventRelationTypeResolver()
        );
    }
);

$app['database.installer'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\CdbXmlService\DatabaseSchemaInstaller($app);
    }
);

$app['labels_relations_schema'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\SchemaConfigurator(
            new StringLiteral('labels_relations')
        );
    }
);

$app['database.installer'] = $app->extend(
    'database.installer',
    function (DatabaseSchemaInstaller $installer, Application $app) {
        $installer->addSchemaConfigurator(
            $app['labels_relations_schema']
        );
        return $installer;
    }
);

$app->register(
    new \CultuurNet\UDB3\CdbXmlService\DoctrineMigrationsServiceProvider(),
    ['migrations.config_file' => __DIR__ . '/migrations.yml']
);

$app['event_cdbid_extractor'] = $app->share(
    function (Application $app) {
        return new EventCdbIdExtractor(
            $app['place_external_id_mapping_service'],
            $app['organizer_external_id_mapping_service']
        );
    }
);

$app['place_external_id_mapping_service'] = $app->share(
    function (Application $app) use ($appConfigLocation) {
        $yamlFileLocation = $appConfigLocation . '/external_id_mapping_place.yml';
        return $app['udb2_external_id_mapping_service_factory']($yamlFileLocation);
    }
);

$app['organizer_external_id_mapping_service'] = $app->share(
    function (Application $app) use ($appConfigLocation) {
        $yamlFileLocation = $appConfigLocation . '/external_id_mapping_organizer.yml';
        return $app['udb2_external_id_mapping_service_factory']($yamlFileLocation);
    }
);

$app['udb2_external_id_mapping_service_factory'] = $app->protect(
    function ($yamlFileLocation) {
        $map = [];

        if (file_exists($yamlFileLocation)) {
            $yaml = file_get_contents($yamlFileLocation);
            $yaml = Yaml::parse($yaml);

            if (is_array($yaml)) {
                $map = $yaml;
            }
        }

        return new ArrayMappingService($map);
    }
);

/**
 * Load additional bootstrap files.
 */
foreach ($app['config']['bootstrap'] as $identifier => $enabled) {
    if (true === $enabled) {
        require __DIR__ . "/bootstrap/{$identifier}.php";
    }
}

return $app;
