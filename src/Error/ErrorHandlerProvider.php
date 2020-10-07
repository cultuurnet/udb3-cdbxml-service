<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CdbXmlService\Error;

use Sentry\State\HubInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[SentryErrorHandler::class] = $app->share(
            function ($app) {
                return new SentryErrorHandler($app[HubInterface::class]);
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
