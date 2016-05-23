#!/usr/bin/env php
<?php

use CultuurNet\SilexAMQP\Console\ConsumeCommand;
use Knp\Provider\ConsoleServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var \Silex\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$app->register(
    new ConsoleServiceProvider(),
    [
        'console.name'              => 'UDB3 cdbxml service',
        'console.version'           => '1.0.0',
        'console.project_directory' => __DIR__ . '/..'
    ]
);

/** @var \Knp\Console\Application $consoleApp */
$consoleApp = $app['console'];

$consoleApp->add(
    (new ConsumeCommand('consume-udb3-core', 'amqp.udb3-core'))
        ->setDescription('Process messages from UDB3 core')
);
$consoleApp->add(new \CultuurNet\UDB3\CdbXmlService\Console\InstallCommand());

$consoleApp->run();
