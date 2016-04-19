<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

/**
 * Formats a timestamp in a date formatted string.
 */
interface DateFormatterInterface
{
    /**
     * @param string $timestamp
     * @return string
     */
    public function format($timestamp);
}
