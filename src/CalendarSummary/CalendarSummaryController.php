<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\CalendarSummary\FormatterException;
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
        $defaultContentType = new ContentType('text/plain');
        $acceptableContentTypes = $request->getAcceptableContentTypes();
        $requestedContentType = reset($acceptableContentTypes);
        $contentType = $requestedContentType ? new ContentType($requestedContentType) : $defaultContentType;

        $calendarFormat = new Format($request->query->get('format', 'lg'));

        $problem = null;
        $defaultProblemDetail = "A problem occurred when trying to show the calendar-summary for offer with id: \"$cdbid\" in format \"$calendarFormat\" as $contentType";

        try {
            $summary = $this->calendarSummaryRepository->get($cdbid, $contentType, $calendarFormat);

            if (is_null($summary)) {
                $problem = new ApiProblem('The summary could not be found.');
                $problem->setDetail($defaultProblemDetail);
                $problem->setStatus(Response::HTTP_NOT_FOUND);
                return $this->createProblemResponse($problem);
            }
        } catch (DocumentGoneException $e) {
            $problem = new ApiProblem('The summary is gone.');
            $problem->setDetail($defaultProblemDetail);
            $problem->setStatus(Response::HTTP_GONE);
            return $this->createProblemResponse($problem);
        } catch (FormatterException $exception) {
            $problem = new ApiProblem('The requested content-type does not support the calendar-format.');
            $problem->setDetail($defaultProblemDetail);
            $problem->setStatus(Response::HTTP_NOT_ACCEPTABLE);
            return $this->createProblemResponse($problem);
        } catch (UnsupportedContentTypeException $exception) {
            $problem = new ApiProblem('Content-type not supported.');
            $problem->setDetail($exception->getMessage());
            $problem->setStatus(Response::HTTP_NOT_ACCEPTABLE);
            return $this->createProblemResponse($problem);
        }

        $response = new Response();
        $response->setContent($summary);
        $response->headers->set('Content-Type', (string) $contentType);
        return $response;
    }

    private function createProblemResponse(ApiProblem $problem): Response
    {
        $problem
            ->setDetail()
            ->setType('about:blank');

        $response = new Response();

        $response
            ->setContent($problem->getTitle())
            ->setStatusCode($problem->getStatus());

        return  $response;
    }
}
