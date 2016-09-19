<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

class AbstractOfferProjectedToCdbXmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $iri;

    /**
     * @var AbstractOfferProjectedToCdbXml|\PHPUnit_Framework_MockObject_MockObject
     */
    private $abstractOfferProjectedToCdbXml;

    protected function setUp()
    {
        $this->iri = 'http://www.google.be';

        $this->abstractOfferProjectedToCdbXml = $this->getMockForAbstractClass(
            AbstractOfferProjectedToCdbXml::class,
            [$this->iri, true]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_iri_property()
    {
        $this->assertEquals(
            $this->iri,
            $this->abstractOfferProjectedToCdbXml->getIri()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_new_property()
    {
        $this->assertEquals(
            true,
            $this->abstractOfferProjectedToCdbXml->isNew()
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_new_property_of_false()
    {
        /** @var AbstractOfferProjectedToCdbXml $abstractOfferProjectedToCdbXml */
        $abstractOfferProjectedToCdbXml = $this->getMockForAbstractClass(
            AbstractOfferProjectedToCdbXml::class,
            [$this->iri]
        );

        $this->assertEquals(
            false,
            $abstractOfferProjectedToCdbXml->isNew()
        );
    }
}
