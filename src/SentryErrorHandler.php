<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CdbXmlService;

use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Throwable;

class SentryErrorHandler
{
    /** @var HubInterface */
    private $sentryHub;

    /** @var bool */
    private $console;

    public function __construct(HubInterface $sentryHub, bool $console)
    {
        $this->sentryHub = $sentryHub;
        $this->console = $console;
    }

    public function handle(Throwable $throwable): void
    {
        $this->sentryHub->configureScope(
            function (Scope $scope) {
                $scope->setTags($this->createTags($this->console));
            }
        );

        $this->sentryHub->captureException($throwable);
    }

    private function createTags(bool $console): array
    {
        return [
            'runtime.env' => $console ? 'cli' : 'web',
        ];
    }
}
