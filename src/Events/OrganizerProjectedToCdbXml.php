<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;

class OrganizerProjectedToCdbXml extends OrganizerEvent
{
    /**
     * @var bool
     */
    protected $isNew;

    public function __construct($organizerId, $isNew = false)
    {
        parent::__construct($organizerId);
        $this->isNew = $isNew;
    }

    public function isNew()
    {
        return $this->isNew;
    }
}
