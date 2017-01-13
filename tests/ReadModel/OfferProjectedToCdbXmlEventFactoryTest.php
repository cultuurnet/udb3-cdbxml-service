<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;
use CultuurNet\UDB3\CdbXmlService\Events\EventProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;

class OfferProjectedToCdbXmlEventFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IriGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $iriGenerator;

    /**
     * @var OfferProjectedToCdbXmlEventFactory
     */
    private $factory;

    /**
     * @var CdbXmlDocumentParser
     */
    private $cdbXmlDocumentParser;

    public function setUp()
    {
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);

        $this->cdbXmlDocumentParser = new CdbXmlDocumentParser();

        $this->factory = new OfferProjectedToCdbXmlEventFactory(
            $this->iriGenerator,
            $this->cdbXmlDocumentParser
        );
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     * @param string $id
     * @param $offerPrefix
     * @param string $iri
     * @param bool $isNew
     * @param $expectedEvent
     * @param string $file
     */
    public function it_converts_the_id_to_an_iri_when_creating_the_event(
        $id,
        $offerPrefix,
        $iri,
        $isNew,
        $expectedEvent,
        $file
    ) {
        $this->iriGenerator->expects($this->once())
            ->method('iri')
            ->with($offerPrefix . $id)
            ->willReturn($iri);

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            file_get_contents(__DIR__ . $file)
        );

        $actualEvent = $this->factory->createEvent($cdbXmlDocument, $isNew);

        $this->assertEquals($expectedEvent, $actualEvent);
    }

    /**
     * @return array
     */
    public function offerDataProvider()
    {
        return [
            [
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                'place/',
                'place/34973B89-BDA3-4A79-96C7-78ACC022907D',
                false,
                new PlaceProjectedToCdbXml(
                    'place/34973B89-BDA3-4A79-96C7-78ACC022907D',
                    false
                ),
                '/Repository/samples/place.xml',
            ],
            [
                '34973B89-BDA3-4A79-96C7-78ACC022907D',
                'place/',
                'place/34973B89-BDA3-4A79-96C7-78ACC022907D',
                true,
                new PlaceProjectedToCdbXml(
                    'place/34973B89-BDA3-4A79-96C7-78ACC022907D',
                    true
                ),
                '/Repository/samples/place.xml',
            ],
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                'event/',
                'event/404EE8DE-E828-9C07-FE7D12DC4EB24480',
                false,
                new EventProjectedToCdbXml(
                    'event/404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    false
                ),
                '/Repository/samples/event.xml',
            ],
            [
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                'event/',
                'event/404EE8DE-E828-9C07-FE7D12DC4EB24480',
                true,
                new EventProjectedToCdbXml(
                    'event/404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    true
                ),
                '/Repository/samples/event.xml',
            ],
        ];
    }
}
