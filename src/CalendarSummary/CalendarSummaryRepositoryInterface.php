<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

interface CalendarSummaryRepositoryInterface
{
    /**
     * @param string $offerId
     * @param ContentType $type
     * @param Format $format
     *
     * @return mixed
     */
    public function get($offerId, ContentType $type, Format $format);
}
