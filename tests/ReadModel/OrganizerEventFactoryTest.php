<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;

class OrganizerEventFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizerEventFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new OrganizerEventFactory();
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
