<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use PHPUnit\Framework\TestCase;

class OrganizerProjectedToCdbXmlEventFactoryTest extends TestCase
{
    /**
     * @var OrganizerProjectedToCdbXmlEventFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new OrganizerProjectedToCdbXmlEventFactory();
    }

    /**
     * @test
     */
    public function it_returns_the_correct_type_of_event()
    {
        $id = 'ORG-123';

        $expectedEvent = new OrganizerProjectedToCdbXml($id);

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            file_get_contents(__DIR__ . '/Repository/samples/actor.xml')
        );

        $actualEvent = $this->factory->createEvent($cdbXmlDocument);

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
