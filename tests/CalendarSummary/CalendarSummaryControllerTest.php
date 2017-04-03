<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\CalendarSummary\FormatterException;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class CalendarSummaryControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CalendarSummaryRepositoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $calendarSummaryRepository;

    /**
     * @var CalendarSummaryController
     */
    private $controller;

    public function setUp()
    {
        $this->calendarSummaryRepository = $this->createMock(CalendarSummaryRepositoryInterface::class);
        $this->controller = new CalendarSummaryController($this->calendarSummaryRepository);
    }

    /**
     * @test
     */
    public function it_should_fetch_a_plain_text_summary_when_no_content_type_is_specified()
    {
        $offerId = '21915201-a582-45b1-a997-4171ac6b71c1';
        $request = new Request();

        $this->calendarSummaryRepository
            ->expects($this->once())
            ->method('get')
            ->with(
                '21915201-a582-45b1-a997-4171ac6b71c1',
                new ContentType('text/plain'),
                new Format('lg')
            );

        $this->controller->get($offerId, $request);
    }

    /**
     * @test
     */
    public function it_should_format_a_summary_as_big_as_possible_when_no_format_is_specified()
    {
        $offerId = '21915201-a582-45b1-a997-4171ac6b71c1';
        $request = new Request();

        $this->calendarSummaryRepository
            ->expects($this->once())
            ->method('get')
            ->with(
                '21915201-a582-45b1-a997-4171ac6b71c1',
                new ContentType('text/plain'),
                new Format('lg')
            );

        $this->controller->get($offerId, $request);
    }

    /**
     * @test
     */
    public function it_should_return_a_summary_in_the_request_content_type_and_format()
    {
        $offerId = '21915201-a582-45b1-a997-4171ac6b71c1';
        /** @var Request|PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['getAcceptableContentTypes', 'getQueryString'])
            ->setConstructorArgs([['format' => 'small']])
            ->getMock();

        $request
            ->expects($this->once())
            ->method('getAcceptableContentTypes')
            ->willReturn(['text/html']);

        $this->calendarSummaryRepository
            ->expects($this->once())
            ->method('get')
            ->with(
                '21915201-a582-45b1-a997-4171ac6b71c1',
                new ContentType('text/html'),
                new Format('small')
            );

        $this->controller->get($offerId, $request);
    }

    /**
     * @test
     * @dataProvider summaryExceptionProvider
     */
    public function it_should_return_a_problem_response_for_each_known_repository_exception(\Exception $exception, $expectedProblem)
    {
        $offerId = '21915201-a582-45b1-a997-4171ac6b71c1';
        $request = new Request();

        $this->calendarSummaryRepository
            ->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $response = $this->controller->get($offerId, $request);
        $this->assertEquals($expectedProblem, $response->getContent());
    }

    public function summaryExceptionProvider()
    {
        return [
            'unsupported format' => [
                'exception' => new UnsupportedContentTypeException('Unsupported content-type: ding/ding'),
                'problem' => 'Content-type not supported.',
            ],
            'offer document gone' => [
                'exception' => new DocumentGoneException('No document found for offer with id: 21915201-a582-45b1-a997-4171ac6b71c1'),
                'problem' => 'The summary is gone.',
            ],
            'calendar-summary exception' => [
                'exception' => new FormatterException('Format not supported'),
                'problem' => 'The requested content-type does not support the calendar-format.',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_return_a_problem_when_the_summary_can_not_be_found()
    {
        $expectedProblem = 'The summary could not be found.';
        $offerId = '21915201-a582-45b1-a997-4171ac6b71c1';
        $request = new Request();

        $this->calendarSummaryRepository
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $response = $this->controller->get($offerId, $request);
        $this->assertEquals($expectedProblem, $response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_a_formatted_calendar_summary()
    {
        $expectedSummary = 'morregen';
        $offerId = '21915201-a582-45b1-a997-4171ac6b71c1';
        $request = new Request();

        $this->calendarSummaryRepository
            ->expects($this->once())
            ->method('get')
            ->willReturn('morregen');

        $response = $this->controller->get($offerId, $request);
        $this->assertEquals($expectedSummary, $response->getContent());
    }
}
