<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

class UitpasLabelApplierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LabelFilterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $uitpasLabelFilter;

    /**
     * @var UitpasLabelApplier
     */
    private $uitpasLabelApplier;

    /**
     * @var \CultureFeed_Cdb_Item_Event
     */
    private $eventWithoutLabels;

    /**
     * @var \CultureFeed_Cdb_Item_Actor
     */
    private $actorWithLabels;

    /**
     * @var \CultureFeed_Cdb_Item_Event
     */
    private $eventWithOneLabel;

    /**
     * @var \CultureFeed_Cdb_Item_Event
     */
    private $eventWithTwoLabels;

    protected function setUp()
    {
        $this->uitpasLabelFilter = $this->getMock(LabelFilterInterface::class);

        $this->uitpasLabelApplier = new UitpasLabelApplier(
            $this->uitpasLabelFilter
        );

        $cdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Samples/event-without-labels.xml')
        );

        $this->eventWithoutLabels = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXmlDocument->getCdbXml()
        );

        $cdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Samples/event-with-one-keyword.xml')
        );

        $this->eventWithOneLabel = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXmlDocument->getCdbXml()
        );

        $cdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Samples/event-with-two-keywords.xml')
        );

        $this->eventWithTwoLabels = EventItemFactory::createEventFromCdbXml(
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
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(['Paspartoe', 'UiTPAS Gent']);

        $this->uitpasLabelApplier->addLabels(
            $this->eventWithoutLabels,
            $this->actorWithLabels
        );

        $this->assertEquals($this->eventWithTwoLabels, $this->eventWithoutLabels);
    }

    /**
     * @test
     */
    public function it_removes_uitpas_labels_attached_to_an_actor_from_an_event()
    {
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(['Paspartoe', 'UiTPAS Gent']);

        $this->uitpasLabelApplier->removeLabels(
            $this->eventWithTwoLabels,
            $this->actorWithLabels
        );

        $this->assertEquals($this->eventWithoutLabels, $this->eventWithTwoLabels);
    }

    /**
     * @test
     */
    public function it_adds_an_uitpas_label_to_an_event()
    {
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(['Paspartoe']);

        $this->uitpasLabelApplier->addLabel(
            $this->eventWithoutLabels,
            "Paspartoe"
        );

        $this->assertEquals($this->eventWithOneLabel, $this->eventWithoutLabels);
    }

    /**
     * @test
     */
    public function it_removes_an_uitpas_label_from_an_event()
    {
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(['Paspartoe']);

        $this->uitpasLabelApplier->removeLabel(
            $this->eventWithOneLabel,
            "Paspartoe"
        );

        $this->assertEquals($this->eventWithoutLabels, $this->eventWithOneLabel);
    }
}
