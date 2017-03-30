<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\CalendarSummary\CalendarFormatterInterface;
use CultuurNet\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummary\FormatterException;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarSummaryController
{
    /**
     * @var CalendarSummaryRepositoryInterface
     */
    protected $calendarSummaryRepository;

    /**
     * @param CalendarSummaryRepositoryInterface $calendarSummaryRepository
     */
    public function __construct(CalendarSummaryRepositoryInterface $calendarSummaryRepository)
    {
        $this->calendarSummaryRepository = $calendarSummaryRepository;
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
        $defaultContentType = new ContentType('text/plain');
        $defaultCalendarFormat = 'lg';

        $calendarFormat = new Format($request->query->get('format', $defaultCalendarFormat));

        $requestedContentType = $request->getAcceptableContentTypes()[0];
        $contentType = $requestedContentType ? new ContentType($requestedContentType) : $defaultContentType;

        $response = new Response();

        try {
            $summary = $this->calendarSummaryRepository->get($cdbid, $contentType, $calendarFormat);

            if (is_null($summary)) {
                $problem = new ApiProblem('The summary could not be found.');
                $problem->setStatus(Response::HTTP_NOT_FOUND);
            } else {
                $response->setContent($summary);
                $response->headers->set('Content-Type', (string) $contentType);
            }
        } catch (DocumentGoneException $e) {
            $problem = new ApiProblem('The summary is gone.');
            $problem->setStatus(Response::HTTP_GONE);
        } catch (FormatterException $exception) {
            $problem = new ApiProblem('The requested content-type does not support the calendar-format.');
            $problem->setStatus(Response::HTTP_NOT_ACCEPTABLE);
        } catch (UnsupportedContentTypeException $exception) {
            $problem = new ApiProblem('Content-type not supported.');
            $problem->setDetail($exception->getMessage());
            $problem->setStatus(Response::HTTP_NOT_ACCEPTABLE);
        }

        if (isset($problem)) {
            $problem
                ->setDetail("A problem occurred when trying to show the calendar-summary for offer with id: \"$cdbid\" in format \"$calendarFormat\" as $contentType")
                ->setType('about:blank');

            $response
                ->setContent($problem->getTitle())
                ->setStatusCode($problem->getStatus());
        }

        return $response;
    }
}
