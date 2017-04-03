<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;

interface CalendarSummaryRepositoryInterface
{
    /**
     * @param string $offerId
     * @param ContentType $type
     * @param Format $format
     *
     * @return string|null
     * @throws UnsupportedContentTypeException
     * @throws DocumentGoneException
     */
    public function get($offerId, ContentType $type, Format $format);
}
