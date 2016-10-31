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
     * @inheritdoc
     */
    public function addLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    ) {
        $organizerLabels = $actor->getKeywords();

        return $this->internAddLabels($event, $organizerLabels);
    }

    /**
     * @inheritdoc
     */
    public function removeLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    ) {
        $organizerLabels = $actor->getKeywords();

        return $this->internRemoveLabels($event, $organizerLabels);
    }

    /**
     * @inheritdoc
     */
    public function addLabel(
        \CultureFeed_Cdb_Item_Event $event,
        $label
    ) {
        return $this->internAddLabels($event, [$label]);
    }

    /**
     * @inheritdoc
     */
    public function removeLabel(
        \CultureFeed_Cdb_Item_Event $event,
        $label
    ) {
        return $this->internRemoveLabels($event, [$label]);
    }

    /**
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param array $labels
     * @return \CultureFeed_Cdb_Item_Event
     */
    private function internAddLabels(
        \CultureFeed_Cdb_Item_Event $event,
        array $labels
    ) {
        $updatedEvent = clone $event;

        $uitpasLabels = $this->uitpasLabelFilter->filter($labels);

        foreach ($uitpasLabels as $uitpasLabel) {
            $updatedEvent->addKeyword($uitpasLabel);
        }

        return $updatedEvent;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param array $labels
     * @return \CultureFeed_Cdb_Item_Event
     */
    private function internRemoveLabels(
        \CultureFeed_Cdb_Item_Event $event,
        array $labels
    ) {
        $updatedEvent = clone $event;

        $uitpasLabels = $this->uitpasLabelFilter->filter($labels);

        $eventLabels = $event->getKeywords();

        foreach ($eventLabels as $eventLabel) {
            if (in_array($eventLabel, $uitpasLabels)) {
                $updatedEvent->deleteKeyword($eventLabel);
            }
        }

        return $updatedEvent;
    }
}
