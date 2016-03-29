<?php

namespace CultuurNet\UDB3\CDBXMLService\Events;

class EnrichedOrganizerUpdatedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_returns_all_properties()
    {
        $itemId = '123';
        $organizerId = '456';
        $name = 'Foo Bar';

        $enrichedOrganizerUpdated = new EnrichedOrganizerUpdated(
            $itemId,
            $organizerId,
            $name
        );

        $this->assertEquals($itemId, $enrichedOrganizerUpdated->getItemId());
        $this->assertEquals($organizerId, $enrichedOrganizerUpdated->getOrganizerId());
        $this->assertEquals($name, $enrichedOrganizerUpdated->getName());
    }
}
