<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\CalendarSummary\CalendarPlainTextFormatter;

class SimpleFormatterLocator implements FormatterLocatorInterface
{
    /**
     * @var array
     */
    private $supportedContentTypeFormatters;

    /**
     * FormatterLocator constructor.
     */
    public function __construct()
    {
        $this->supportedContentTypeFormatters = [
            'text/plain' => CalendarPlainTextFormatter::class,
            'text/html' => CalendarHTMLFormatter::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterForContentType(ContentType $contentType)
    {
        if (!array_key_exists((string) $contentType, $this->supportedContentTypeFormatters)) {
            throw new UnsupportedContentTypeException("Content-type: $contentType is not supported!");
        }

        $formatterClassName = $this->supportedContentTypeFormatters[(string) $contentType];
        $formatter = new $formatterClassName();

        return $formatter;
    }
}
