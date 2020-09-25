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
        $app[UncaughtErrorHandler::class] = $app->share(
            function ($app) {
                return new UncaughtErrorHandler($app[HubInterface::class]);
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
