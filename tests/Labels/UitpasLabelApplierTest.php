<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

class UitpasLabelApplierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LabelFilterInterface
     */
    private $uitpasLabelFilter;

    /**
     * @var UitpasLabelApplier
     */
    private $uitpasLabelApplier;

    /**
     * @var \CultureFeed_Cdb_Item_Event
     */
    private $event;

    /**
     * @var \CultureFeed_Cdb_Item_Actor
     */
    private $actorWithLabels;

    /**
     * @var \CultureFeed_Cdb_Item_Event
     */
    private $eventWithLabels;

    protected function setUp()
    {
        $this->uitpasLabelFilter = $this->getMock(LabelFilterInterface::class);
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(['Paspartoe', 'UiTPAS Gent']);

        $this->uitpasLabelApplier = new UitpasLabelApplier(
            $this->uitpasLabelFilter
        );

        $cdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Samples/event.xml')
        );

        $this->event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXmlDocument->getCdbXml()
        );

        $cdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Samples/event-with-keywords.xml')
        );

        $this->eventWithLabels = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXmlDocument->getCdbXml()
        );

        $cdbXmlDocument = new CdbXmlDocument(
            'ORG-123',
            file_get_contents(__DIR__ . '/Samples/actor-with-keywords.xml')
        );

        $this->actorWithLabels = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXmlDocument->getCdbXml()
        );
    }

    /**
     * @test
     */
    public function it_adds_uitpas_labels_attached_to_an_actor_to_an_event()
    {
        $this->uitpasLabelApplier->addLabels(
            $this->event,
            $this->actorWithLabels
        );

        $this->assertEquals($this->eventWithLabels, $this->event);
    }

    /**
     * @test
     */
    public function it_removes_uitpas_labels_attached_to_an_actor_from_an_event()
    {
        $this->uitpasLabelApplier->removeLabels(
            $this->eventWithLabels,
            $this->actorWithLabels
        );

        $this->assertEquals($this->event, $this->eventWithLabels);
    }
}
