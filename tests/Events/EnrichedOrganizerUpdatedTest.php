<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Event\Events\OrganizerUpdated;

class EnrichedOrganizerUpdatedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_returns_all_properties()
    {
        $organizerUpdated = new OrganizerUpdated(
            'event-id-123',
            'organizer-id-456'
        );

        $name = 'Organizer Name';

        $enrichedOrganizerUpdated = new EnrichedOrganizerUpdated(
            $organizerUpdated,
            $name
        );

        $this->assertEquals($organizerUpdated, $enrichedOrganizerUpdated->getOriginalEvent());
        $this->assertEquals($name, $enrichedOrganizerUpdated->getName());
    }
}
