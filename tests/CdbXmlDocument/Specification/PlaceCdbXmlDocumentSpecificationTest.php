<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

class PlaceCdbXmlDocumentSpecificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceCdbXmlDocumentSpecification
     */
    private $specification;

    public function setUp()
    {
        $this->specification = new PlaceCdbXmlDocumentSpecification();
    }

    /**
     * @test
     */
    public function it_is_satisfied_if_the_document_contains_place_actor_cdbxml()
    {
        $xml = file_get_contents(__DIR__ . '/../samples/place.xml');
        $cdbXmlDocument = new CdbXmlDocument(1, $xml);
        $this->assertTrue($this->specification->isSatisfiedBy($cdbXmlDocument));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_if_the_document_does_not_contain_place_actor_cdbxml()
    {
        $xml = file_get_contents(__DIR__ . '/../samples/event.xml');
        $cdbXmlDocument = new CdbXmlDocument(1, $xml);
        $this->assertFalse($this->specification->isSatisfiedBy($cdbXmlDocument));
    }
}
