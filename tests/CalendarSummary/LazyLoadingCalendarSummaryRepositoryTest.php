<?php

namespace CultuurNet\UDB3\CdbXmlService\CalendarSummary;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LazyLoadingCalendarSummaryRepositoryTest extends TestCase
{
    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $cdbxmlRepository;

    /**
     * @var LazyLoadingCalendarSummaryRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $cdbXmlFilesPath = __DIR__ . '/../CdbXmlDocument/samples';

    protected function setUp(): void
    {
        $this->cdbxmlRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->repository = new LazyLoadingCalendarSummaryRepository(
            $this->cdbxmlRepository,
            new SimpleFormatterLocator()
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_error_when_getting_an_unsupported_content_type()
    {
        $this->expectException(UnsupportedContentTypeException::class);

        $this->repository->get(
            '261d61d4-38d8-46f5-a2d4-7299cc44129c',
            new ContentType('application/json'),
            new Format('huge')
        );
    }

    /**
     * @test
     */
    public function it_should_return_null_when_no_cdbxml_document_exist_for_the_given_offer()
    {
        $this->cdbxmlRepository
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $summary = $this->repository->get(
            '261d61d4-38d8-46f5-a2d4-7299cc44129c',
            new ContentType('text/plain'),
            new Format('huge')
        );

        $this->assertNull($summary);
    }

    /**
     * @test
     * @dataProvider calendarSummaryContentProvider
     */
    public function it_should_return_a_calendar_summary_for_supported_content_types_in_the_given_format(
        $offerId,
        ContentType $contentType,
        $expectedCalendarSummary,
        CdbXmlDocument $cdbXmlDocument
    ) {
        $this->cdbxmlRepository
            ->expects($this->once())
            ->method('get')
            ->with($offerId)
            ->willReturn($cdbXmlDocument);

        $summary = $this->repository->get(
            $offerId,
            $contentType,
            new Format('lg')
        );

        $this->assertEquals($expectedCalendarSummary, $summary);
    }

    public function calendarSummaryContentProvider()
    {
        return [
            'plain text' => [
                'offerId' => '6ad50874-9513-403e-a9b8-444e848bd217',
                'contentType' => new ContentType('text/plain'),
                'expectedCalendarSummary' => '',
                'cdbxmlDocument' => new CdbXmlDocument(
                    '6ad50874-9513-403e-a9b8-444e848bd217',
                    $this->loadCdbXmlFromFile('event.xml')
                ),
            ],
            'html' => [
                'offerId' => '6ad50874-9513-403e-a9b8-444e848bd217',
                'contentType' => new ContentType('text/html'),
                'expectedCalendarSummary' => '<ul class="list-unstyled"></ul>',
                'cdbxmlDocument' => new CdbXmlDocument(
                    '6ad50874-9513-403e-a9b8-444e848bd217',
                    $this->loadCdbXmlFromFile('event.xml')
                ),
            ],
        ];
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function loadCdbXmlFromFile($fileName)
    {
        return file_get_contents($this->cdbXmlFilesPath . '/' . $fileName);
    }
}
