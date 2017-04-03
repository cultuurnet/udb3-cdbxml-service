<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\CalendarSummary\CalendarFormatterInterface;

interface FormatterLocatorInterface
{
    /**
     * @param ContentType $contentType
     * @return CalendarFormatterInterface
     */
    public function getFormatterForContentType(ContentType $contentType);
}
