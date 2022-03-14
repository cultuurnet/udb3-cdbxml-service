<?php

namespace CultuurNet\UDB3\Organizer\Events;

final class WebsiteUpdated extends OrganizerEvent
{
    /**
     * @var string
     */
    private $website;

    public function __construct(
        string $organizerId,
        string $website
    ) {
        parent::__construct($organizerId);
        $this->website = $website;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'website' => $this->getWebsite(),
        ];
    }

    public static function deserialize(array $data): WebsiteUpdated
    {
        return new static(
            $data['organizer_id'],
            $data['website']
        );
    }
}
