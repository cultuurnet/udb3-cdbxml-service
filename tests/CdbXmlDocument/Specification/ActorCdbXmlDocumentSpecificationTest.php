<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;
use PHPUnit\Framework\TestCase;

class ActorCdbXmlDocumentSpecificationTest extends TestCase
{
    /**
     * @var CdbXmlDocumentParser
     */
    private $parser;

    /**
     * @var ActorCdbXmlDocumentSpecification
     */
    private $specification;

    protected function setUp(): void
    {
        $this->parser = new CdbXmlDocumentParser();
        $this->specification = new ActorCdbXmlDocumentSpecification($this->parser);
    }

    /**
     * @test
     */
    public function it_is_satisfied_if_the_document_contains_actor_cdbxml()
    {
        $xml = file_get_contents(__DIR__ . '/../samples/actor.xml');
        $cdbXmlDocument = new CdbXmlDocument(1, $xml);
        $this->assertTrue($this->specification->isSatisfiedBy($cdbXmlDocument));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_if_the_document_does_not_contain_actor_cdbxml()
    {
        $xml = file_get_contents(__DIR__ . '/../samples/event.xml');
        $cdbXmlDocument = new CdbXmlDocument(1, $xml);
        $this->assertFalse($this->specification->isSatisfiedBy($cdbXmlDocument));
    }
}
