<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

/**
 * Formats a timestamp in the date format used in cdbxml.
 */
class CdbXmlDateFormatter implements DateFormatterInterface
{
    /**
     * @param int $timestamp
     * @return string
     */
    public function format($timestamp)
    {
        if (!is_int($timestamp)) {
            $type = gettype($timestamp);
            throw new \InvalidArgumentException("Timestamp should be of type int, {$type} given.");
        }

        $dateTime = (new \DateTime())
            ->setTimestamp($timestamp);

        // Close to ISO-8601 but not exactly.
        return $dateTime->format('Y-m-d\TH:i:s');
    }
}
