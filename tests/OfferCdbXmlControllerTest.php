<?php

namespace CultuurNet\UDB3\CdbXmlService;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentGoneException;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class OfferCdbXmlControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CdbXmlDocumentController
     */
    protected $controller;

    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    public function setUp()
    {
        $this->repository = $this->createMock(DocumentRepositoryInterface::class);
        $this->controller = new CdbXmlDocumentController($this->repository);
    }

    /**
     * @test
     */
    public function it_should_return_an_xml_response_when_a_cdbxml_is_found()
    {
        $offerXml = '<?xml version="1.0"?><_/>';
        $cdbid = '2AF0FB32-BBF5-4E27-9D47-F0BBEAE340D9';

        $this->repository
            ->method('get')
            ->with($cdbid)
            ->willReturn(new CdbXmlDocument($cdbid, $offerXml));

        $actualResponse = $this->controller->get($cdbid);

        $expectedResponse = new Response($offerXml, 200, ['Content-Type' => 'application/xml']);

        $this->assertResponseEquals($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function it_should_state_the_problem_when_unable_to_retrieve_an_cdbxml_offer()
    {
        $problemXml = file_get_contents(__DIR__ . '/responses/document-not-found.xml');
        $cdbid = '2AF0FB32-BBF5-4E27-9D47-F0BBEAE340D9';

        $this->repository
            ->method('get')
            ->with($cdbid)
            ->willReturn(null);

        $actualResponse = $this->controller->get($cdbid);
        $expectedResponse = new Response($problemXml, 404, ['Content-Type' => 'application/xml']);

        $this->assertResponseEquals($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function it_should_state_the_problem_when_the_document_for_a_cdbxml_offer_has_been_removed()
    {
        $problemXml = file_get_contents(__DIR__ . '/responses/document-gone.xml');
        $cdbid = '93F76D64-12A0-4691-87CF-8F0C652DF0EE';

        $this->repository
            ->method('get')
            ->with($cdbid)
            ->willThrowException(new DocumentGoneException());

        $actualResponse = $this->controller->get($cdbid);
        $expectedResponse = new Response($problemXml, 410, ['Content-Type' => 'application/xml']);

        $this->assertResponseEquals($expectedResponse, $actualResponse);
    }

    /**
     * @param Response $expected
     * @param Response $actual
     */
    private function assertResponseEquals(Response $expected, Response $actual)
    {
        $this->assertEquals(get_class($expected), get_class($actual));
        $this->assertEquals($expected->getContent(), $actual->getContent());
        $this->assertEquals($expected->getStatusCode(), $actual->getStatusCode());
        $this->assertEquals($expected->headers, $actual->headers);
    }
}
