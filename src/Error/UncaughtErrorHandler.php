<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CdbXmlService\Error;

use Sentry\State\HubInterface;
use Throwable;

class UncaughtErrorHandler
{
    /** @var HubInterface */
    private $sentryHub;

    public function __construct(HubInterface $sentryHub)
    {
        $this->sentryHub = $sentryHub;
    }

    public function handle(Throwable $throwable): void
    {
        $this->sentryHub->captureException($throwable);
        throw $throwable;
    }
}
