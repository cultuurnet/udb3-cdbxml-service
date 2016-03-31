<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;

/**
 * Generic "OrganizerUpdated" event with additional info.
 *
 * @method AbstractOrganizerUpdated getOriginalEvent()
 */
class EnrichedOrganizerUpdated extends AbstractEnrichedEvent
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param AbstractOrganizerUpdated $originalEvent
     * @param string $name
     */
    public function __construct(AbstractOrganizerUpdated $originalEvent, $name)
    {
        parent::__construct($originalEvent);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
