<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

/**
 * Allow to use services as controllers.
 */
$app->register(new ServiceControllerServiceProvider());

$app->get('/event/{cdbid}', 'cdbxml_offer.controller:get');
$app->get('/place/{cdbid}', 'cdbxml_offer.controller:get');

$app->run();
