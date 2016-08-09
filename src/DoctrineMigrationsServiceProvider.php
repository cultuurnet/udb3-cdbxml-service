<?php

namespace CultuurNet\UDB3\CdbXmlService;

use CultuurNet\UDB3\CdbXmlService\DatabaseSchemaInstaller;
use CultuurNet\UDB3\CdbXmlService\MigrationsDecoratedDatabaseSchemaInstaller;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DoctrineMigrationsServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['migrations.configuration'] = $app->share(
            function (Application $app) {
                /** @var \Doctrine\DBAL\Connection $connection */
                $connection = $app['dbal_connection'];

                $configuration = new YamlConfiguration($connection);
                $configuration->load($app['migrations.config_file']);

                return $configuration;
            }
        );

        $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer = new MigrationsDecoratedDatabaseSchemaInstaller(
                    $installer,
                    $app['migrations.configuration']
                );

                return $installer;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
