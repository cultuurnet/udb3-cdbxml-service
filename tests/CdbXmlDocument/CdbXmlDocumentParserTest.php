<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

class CdbXmlDocumentParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CdbXmlDocumentParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new CdbXmlDocumentParser();
    }

    /**
     * @test
     */
    public function it_parses_valid_xml()
    {
        $xml = '<cdbxml><test></test></cdbxml>';
        $cdbXmlDocument = new CdbXmlDocument(1, $xml);

        $simpleXmlElement = $this->parser->parse($cdbXmlDocument);

        $this->assertInstanceOf(\SimpleXMLElement::class, $simpleXmlElement);
        $this->assertEquals('cdbxml', $simpleXmlElement->getName());
        $this->assertInstanceOf(\SimpleXMLElement::class, $simpleXmlElement->test);
    }

    /**
     * @test
     */
    public function it_throws_an_invalid_argument_exception_if_xml_is_invalid()
    {
        $xml = '<cdbxml></invalid>';
        $cdbXmlDocument = new CdbXmlDocument(1, $xml);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'CdbXml could not be parsed.'
        );

        $this->parser->parse($cdbXmlDocument);
    }
}
