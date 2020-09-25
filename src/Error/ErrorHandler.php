<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CdbXmlService\Error;

interface ErrorHandler
{
    public function handle(\Throwable $throwable): void;
}
