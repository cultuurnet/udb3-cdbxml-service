<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;

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
        LabelCollection $labelCollection
    ) {
        $updatedEvent = clone $event;

        $uitpasLabels = $this->uitpasLabelFilter->filter($labelCollection)->toStrings();

        foreach ($uitpasLabels as $uitpasLabel) {
            $updatedEvent->addKeyword($uitpasLabel);
        }

        return $updatedEvent;
    }

    /**
     * @inheritdoc
     */
    public function removeLabels(
        \CultureFeed_Cdb_Item_Event $event,
        LabelCollection $labelCollection
    ) {
        $updatedEvent = clone $event;

        $uitpasLabels = $this->uitpasLabelFilter->filter($labelCollection)->toStrings();

        $eventLabels = $event->getKeywords();

        foreach ($eventLabels as $eventLabel) {
            if (in_array($eventLabel, $uitpasLabels)) {
                $updatedEvent->deleteKeyword($eventLabel);
            }
        }

        return $updatedEvent;
    }
}
