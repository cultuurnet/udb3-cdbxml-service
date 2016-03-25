<?php

namespace CultuurNet\UDB3\CDBXMLService;

/**
 * Generic "OrganizerUpdated" event with additional info.
 * Makes no assumptions about offer type because it doesn't matter for the CDBXML projections.
 */
class EnrichedOrganizerUpdated
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $itemId
     * @param string $organizerId
     * @param string $name
     */
    public function __construct($itemId, $organizerId, $name)
    {
        $this->itemId = $itemId;
        $this->organizerId = $organizerId;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function getOrganizerId()
    {
        return $this->organizerId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
