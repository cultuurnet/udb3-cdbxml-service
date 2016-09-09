<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;

class PlaceProjectedToCdbXmlEventFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IriGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $iriGenerator;

    /**
     * @var OfferProjectedToCdbXmlEventFactory
     */
    private $factory;

    public function setUp()
    {
        $this->iriGenerator = $this->getMock(IriGeneratorInterface::class);

        $this->factory = new OfferProjectedToCdbXmlEventFactory(
            $this->iriGenerator
        );
    }

    /**
     * @test
     */
    public function it_converts_the_id_to_an_iri_when_creating_the_event()
    {
        $id = '34973B89-BDA3-4A79-96C7-78ACC022907D';
        $iri = 'place/34973B89-BDA3-4A79-96C7-78ACC022907D';
        $expectedEvent = new PlaceProjectedToCdbXml($iri);

        $this->iriGenerator->expects($this->once())
            ->method('iri')
            ->with('place/' . $id)
            ->willReturn($iri);

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            file_get_contents(__DIR__ . '/Repository/samples/' . 'place.xml')
        );

        $actualEvent = $this->factory->createEvent($cdbXmlDocument);

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
