<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

class UitpasLabelApplier implements LabelApplierInterface
{
    /**
     * @var LabelFilterInterface
     */
    private $uitpasLabelFilter;

    /**
     * UitpasLabelApplier constructor.
     * @param LabelFilterInterface $uitpasLabelFilter
     */
    public function __construct(LabelFilterInterface $uitpasLabelFilter)
    {
        $this->uitpasLabelFilter = $uitpasLabelFilter;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param \CultureFeed_Cdb_Item_Actor $actor
     */
    public function addLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    ) {
        $organizerKeywords = $actor->getKeywords();
        $organizerUitpasKeywords = $this->uitpasLabelFilter->filter($organizerKeywords);

        foreach ($organizerUitpasKeywords as $organizerUitpasKeyword) {
            $event->addKeyword($organizerUitpasKeyword);
        }
    }

    /**
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param \CultureFeed_Cdb_Item_Actor $actor
     */
    public function removeLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    ) {
        $organizerKeywords = $actor->getKeywords();
        $uitpasOrganizerKeywords = $this->uitpasLabelFilter->filter($organizerKeywords);

        $eventKeywords = $event->getKeywords();

        foreach ($eventKeywords as $eventKeyword) {
            if (in_array($eventKeyword, $uitpasOrganizerKeywords)) {
                $event->deleteKeyword($eventKeyword);
            }
        }
    }
}
