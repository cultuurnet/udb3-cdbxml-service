<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\LabelCollection;

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
        $this->uitpasLabelFilter = $this->createMock(LabelFilterInterface::class);

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
            ->willReturn(LabelCollection::fromStrings(['Paspartoe', 'UiTPAS Gent']));

        $updatedEvent = $this->uitpasLabelApplier->addLabels(
            $this->eventWithoutLabels,
            LabelCollection::fromStrings($this->actorWithLabels->getKeywords())
        );

        $this->assertEquals($this->eventWithTwoLabels, $updatedEvent);
    }

    /**
     * @test
     */
    public function it_removes_uitpas_labels_attached_to_an_actor_from_an_event()
    {
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(LabelCollection::fromStrings(['Paspartoe', 'UiTPAS Gent']));

        $updatedEvent = $this->uitpasLabelApplier->removeLabels(
            $this->eventWithTwoLabels,
            LabelCollection::fromStrings($this->actorWithLabels->getKeywords())
        );

        $this->assertEquals($this->eventWithoutLabels, $updatedEvent);
    }

    /**
     * @test
     */
    public function it_adds_an_uitpas_label_to_an_event()
    {
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(LabelCollection::fromStrings(['Paspartoe']));

        $updatedEvent = $this->uitpasLabelApplier->addLabels(
            $this->eventWithoutLabels,
            LabelCollection::fromStrings(["Paspartoe"])
        );

        $this->assertEquals($this->eventWithOneLabel, $updatedEvent);
    }

    /**
     * @test
     */
    public function it_removes_an_uitpas_label_from_an_event()
    {
        $this->uitpasLabelFilter->method('filter')
            ->willReturn(LabelCollection::fromStrings(['Paspartoe']));

        $updatedEvent = $this->uitpasLabelApplier->removeLabels(
            $this->eventWithOneLabel,
            LabelCollection::fromStrings(["Paspartoe"])
        );

        $this->assertEquals($this->eventWithoutLabels, $updatedEvent);
    }
}
