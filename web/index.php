<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\CdbXmlService\SentryErrorHandler;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

/**
 * Allow to use services as controllers.
 */
$app->register(new ServiceControllerServiceProvider());

// Enable CORS.
$app->after($app["cors"]);

$app->get('/event/{cdbid}', 'cdbxml_offer.controller:get');
$app->get('/place/{cdbid}', 'cdbxml_offer.controller:get');

// Should be GET /organizers/{cdbid} but we also allow
// GET /organizer/{cdbid} for backwards compatibility reasons.
$app->get('/organizer/{cdbid}', 'cdbxml_actor.controller:get');
$app->get('/organizers/{cdbid}', 'cdbxml_actor.controller:get');

// calendar-summary
$app->get('/event/{cdbid}/calendar-summary', 'calendar_summary.controller:get');

try {
    $app->run();
} catch (Throwable $throwable) {
    $app[SentryErrorHandler::class]->handle($throwable);
    throw $throwable;
}
