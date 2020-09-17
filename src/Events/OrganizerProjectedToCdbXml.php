<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;

final class OrganizerProjectedToCdbXml extends OrganizerEvent
{
    /**
     * @var bool
     */
    protected $isNew;

    public function __construct(string $organizerId, bool $isNew = false)
    {
        parent::__construct($organizerId);
        $this->isNew = $isNew;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public static function deserialize(array $data): OrganizerProjectedToCdbXml
    {
        return new self($data['organizer_id']);
    }
}
