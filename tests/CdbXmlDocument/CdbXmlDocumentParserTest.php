<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

use PHPUnit\Framework\TestCase;

class CdbXmlDocumentParserTest extends TestCase
{
    /**
     * @var CdbXmlDocumentParser
     */
    private $parser;

    protected function setUp(): void
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

        $this->expectException(
            \InvalidArgumentException::class,
            'CdbXml could not be parsed.'
        );

        $this->parser->parse($cdbXmlDocument);
    }
}
