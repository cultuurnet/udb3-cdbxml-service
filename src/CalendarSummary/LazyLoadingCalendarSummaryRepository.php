<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\CalendarSummary\FormatterException;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;

class LazyLoadingCalendarSummaryRepository implements CalendarSummaryRepositoryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $offerCdbxmlRepository;


    /**
     * @var CdbXmlDocumentParserInterface
     */
    private $cdbxmlParser;

    /**
     * @var FormatterLocatorInterface
     */
    private $formatterLocator;

    /**
     * CalendarSummaryRepository constructor.
     * @param DocumentRepositoryInterface $offerCdbxmlRepository
     * @param FormatterLocatorInterface $formatterLocator
     */
    public function __construct(
        DocumentRepositoryInterface $offerCdbxmlRepository,
        FormatterLocatorInterface $formatterLocator
    ) {
        $this->offerCdbxmlRepository = $offerCdbxmlRepository;
        $this->cdbxmlParser = new CdbXmlDocumentParser();
        $this->formatterLocator = $formatterLocator;
    }

    /**
     * {@inheritdoc}
     *
     * @throws FormatterException
     */
    public function get($offerId, ContentType $type, Format $format)
    {
        $formatter = $this->formatterLocator->getFormatterForContentType($type);

        $cdbxmlDocument = $this->offerCdbxmlRepository->get($offerId);
        if (!$cdbxmlDocument) {
            return null;
        }

        $eventCdbxml = $this->cdbxmlParser->parse($cdbxmlDocument)->event;
        $cdbEvent = \CultureFeed_Cdb_Item_Event::parseFromCdbXml($eventCdbxml);
        $calendarSummary = $formatter->format($cdbEvent->getCalendar(), (string) $format);

        return $calendarSummary;
    }
}
