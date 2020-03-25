<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;
use PHPUnit\Framework\TestCase;

class AbstractOfferProjectedToCdbXmlTest extends TestCase
{
    /**
     * @var string
     */
    private $iri;

    /**
     * @var string
     */
    private $itemId;

    /**
     * @var AbstractOfferProjectedToCdbXml|\PHPUnit_Framework_MockObject_MockObject
     */
    private $abstractOfferProjectedToCdbXml;

    protected function setUp()
    {
        $this->itemId = '7d47c27d-1f0f-461b-9471-07bfaa7b0c56';
        $this->iri = 'http://www.google.be';

        $this->abstractOfferProjectedToCdbXml = $this->getMockForAbstractClass(
            AbstractOfferProjectedToCdbXml::class,
            [$this->itemId, $this->iri, true]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_itemId_property()
    {
        $this->assertEquals(
            $this->itemId,
            $this->abstractOfferProjectedToCdbXml->getItemId()
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
            [$this->itemId, $this->iri]
        );

        $this->assertEquals(
            false,
            $abstractOfferProjectedToCdbXml->isNew()
        );
    }
}
