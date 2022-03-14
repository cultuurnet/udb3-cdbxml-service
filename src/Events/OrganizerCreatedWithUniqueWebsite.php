<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;
use CultuurNet\UDB3\Title;

final class OrganizerCreatedWithUniqueWebsite extends OrganizerEvent
{
    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var string
     */
    private $website;

    /**
     * @var Title
     */
    private $title;

    public function __construct(
        string $id,
        Language $mainLanguage,
        string $website,
        Title $title
    ) {
        parent::__construct($id);

        $this->mainLanguage = $mainLanguage;
        $this->website = $website;
        $this->title = $title;
    }

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'main_language' => $this->getMainLanguage()->getCode(),
                'website' => $this->getWebsite(),
                'title' => (string) $this->getTitle(),
            ];
    }

    public static function deserialize(array $data): OrganizerCreatedWithUniqueWebsite
    {
        return new static(
            $data['organizer_id'],
            new Language($data['main_language']),
            $data['website'],
            new Title($data['title'])
        );
    }
}
