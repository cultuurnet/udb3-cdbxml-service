<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\CalendarSummary\CalendarFormatterInterface;
use CultuurNet\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummary\FormatterException;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;

class CalendarSummaryRepository implements CalendarSummaryRepositoryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $offerCdbxmlRepository;

    /**
     * @var array
     */
    private $supportedContentTypeFormatters;

    /**
     * @var CdbXmlDocumentParserInterface
     */
    private $cdbxmlParser;

    /**
     * CalendarSummaryRepository constructor.
     * @param DocumentRepositoryInterface $offerCdbxmlRepository
     */
    public function __construct(DocumentRepositoryInterface $offerCdbxmlRepository)
    {
        $this->offerCdbxmlRepository = $offerCdbxmlRepository;
        $this->cdbxmlParser = new CdbXmlDocumentParser();

        $this->supportedContentTypeFormatters = [
          'text/plain' => CalendarPlainTextFormatter::class,
          'text/html' => CalendarHTMLFormatter::class,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws FormatterException
     */
    public function get($offerId, ContentType $type, Format $format)
    {
        $formatter = $this->getTypeFormatter($type);

        $cdbxmlDocument = $this->offerCdbxmlRepository->get($offerId);
        if (!$cdbxmlDocument) {
            return null;
        }

        $eventCdbxml = $this->cdbxmlParser->parse($cdbxmlDocument)->event;
        $cdbEvent = \CultureFeed_Cdb_Item_Event::parseFromCdbXml($eventCdbxml);
        $calendarSummary = $formatter->format($cdbEvent->getCalendar(), (string) $format);

        return $calendarSummary;
    }

    /**
     * @param ContentType $type
     *
     * @return CalendarFormatterInterface
     *
     * @throws UnsupportedContentTypeException
     */
    private function getTypeFormatter(ContentType $type)
    {
        if (!array_key_exists((string) $type, $this->supportedContentTypeFormatters)) {
            throw new UnsupportedContentTypeException("Content-type: $type is not supported!");
        }

        $formatterClassName = $this->supportedContentTypeFormatters[(string) $type];
        $formatter = new $formatterClassName();

        return $formatter;
    }
}
