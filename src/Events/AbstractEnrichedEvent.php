<?php

namespace CultuurNet\UDB3\CDBXMLService\Events;

abstract class AbstractEnrichedEvent
{
    /**
     * @var mixed
     */
    private $originalEvent;

    /**
     * @param mixed $originalEvent
     */
    public function __construct($originalEvent)
    {
        $this->originalEvent = $originalEvent;
    }

    /**
     * @return mixed
     */
    public function getOriginalEvent()
    {
        return $this->originalEvent;
    }
}
