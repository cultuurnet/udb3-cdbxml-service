<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\CalendarSummary\CalendarFormatterInterface;
use CultuurNet\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummary\FormatterException;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarSummaryController
{
    /**
     * @var DocumentRepositoryInterface
     */
    protected $documentRepository;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     */
    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * @param $cdbid
     * @return Response
     */
    public function get($cdbid, Request $request)
    {
        $supportedContentTypeFormatters = [
          'text/plain' => CalendarPlainTextFormatter::class,
          'text/html' => CalendarHTMLFormatter::class,
        ];
        $defaultContentType = 'text/plain';
        $defaultCalendarFormat = 'lg';

        $calendarFormat = $request->query->get('format', $defaultCalendarFormat);

        $requestedContentType = $request->getAcceptableContentTypes()[0];
        $contentTypeSupported = array_key_exists($requestedContentType, $supportedContentTypeFormatters);
        $contentType = $contentTypeSupported ? $requestedContentType : $defaultContentType;

        $response = new Response();
        $response->headers->set('Content-Type', $contentType);

        try {
            $document = $this->documentRepository->get($cdbid);

            if (is_null($document)) {
                $problem = new ApiProblem('The document could not be found.');
                $problem->setStatus(Response::HTTP_NOT_FOUND);
            }
        } catch (DocumentGoneException $e) {
            $problem = new ApiProblem('The document is gone.');
            $problem->setStatus(Response::HTTP_GONE);
        }

        if (isset($document)) {
            $cdbxmlParser = new CdbXmlDocumentParser();
            /** @var CalendarFormatterInterface $formatter */
            $formatter = new $supportedContentTypeFormatters[$contentType]();

            $eventCdbxml = $cdbxmlParser->parse($document)->event;
            $cdbEvent = \CultureFeed_Cdb_Item_Event::parseFromCdbXml($eventCdbxml);
            try {
                $calendarSummary = $formatter->format($cdbEvent->getCalendar(), $calendarFormat);
                $response->setContent($calendarSummary);
            } catch (FormatterException $exception) {
                $problem = new ApiProblem('The requested content-type does not support the calendar-format.');
                $problem->setStatus(Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($problem)) {
            $problem
                ->setDetail('A problem occurred when trying to show the calendar-summary for document with id: ' .$cdbid)
                ->setType('about:blank');

            $response
                ->setContent($problem->getTitle())
                ->setStatusCode($problem->getStatus());
        }

        return $response;
    }
}
