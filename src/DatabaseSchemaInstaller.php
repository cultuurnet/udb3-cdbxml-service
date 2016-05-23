<?php

namespace CultuurNet\UDB3\CdbXmlService;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Silex\Application;

class DatabaseSchemaInstaller implements DatabaseSchemaInstallerInterface
{

    protected $app;

    /**
     * @var SchemaConfiguratorInterface[]
     */
    protected $schemaConfigurators;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->schemaConfigurators = [];
    }

    public function addSchemaConfigurator(
        SchemaConfiguratorInterface $schemaConfigurator
    ) {
        $this->schemaConfigurators[] = $schemaConfigurator;
    }

    public function installSchema()
    {
        /** @var \Broadway\EventStore\DBALEventStore[] $stores */
        $stores = array(
            $this->app['event_relations_repository'],
            $this->app['place_relations_repository'],
        );

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->app['dbal_connection'];

        $schemaManager = $connection->getSchemaManager();
        $schema = $schemaManager->createSchema();

        foreach ($stores as $store) {
            $table = $store->configureSchema($schema);
            if ($table) {
                $schemaManager->createTable($table);
            }
        }

        foreach ($this->schemaConfigurators as $configurator) {
            $configurator->configure($schemaManager);
        }
    }
}
