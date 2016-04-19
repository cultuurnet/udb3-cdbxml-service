<?php

namespace CultuurNet\UDB3\CdbXmlService;

use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class OfferCdbXmlControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OfferCdbXmlController
     */
    protected $controller;

    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    public function setUp()
    {
        $this->repository = $this->getMock(DocumentRepositoryInterface::class);
        $this->controller = new OfferCdbXmlController($this->repository);
    }

    /**
     * @test
     */
    public function it_should_return_an_xml_response_when_a_cdbxml_is_found()
    {
        $offerXml = '<?xml version=\'1.0\'?><_/>';
        $cdbid = '2AF0FB32-BBF5-4E27-9D47-F0BBEAE340D9';

        $this->repository
            ->method('get')
            ->with($cdbid)
            ->willReturn(new CdbXmlDocument($cdbid, $offerXml));

        $actualResponse = $this->controller->get($cdbid);
        $expectedResponse = new Response($offerXml, 200, ['Content-Type' => 'xml']);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function it_should_state_the_problem_when_unable_to_retrieve_an_cdbxml_offer()
    {
        $problemXml = file_get_contents(__DIR__ . '/xml-problem-response.xml');
        $cdbid = '2AF0FB32-BBF5-4E27-9D47-F0BBEAE340D9';

        $this->repository
            ->method('get')
            ->with($cdbid)
            ->willReturn(null);

        $actualResponse = $this->controller->get($cdbid);
        $expectedResponse = new Response($problemXml, 404, ['Content-Type' => 'xml']);

        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
