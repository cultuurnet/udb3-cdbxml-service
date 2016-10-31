<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

interface LabelApplierInterface
{
    /**
     * Add only the UiTPAS labels from the provided organizer to the provided event.
     *
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param \CultureFeed_Cdb_Item_Actor $actor
     */
    public function addLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    );

    /**
     * Remove only the UiTPAS labels from the provided organizer to the provided event.
     *
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param \CultureFeed_Cdb_Item_Actor $actor
     */
    public function removeLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    );

    /**
     * Add the given label to the event, only when UiTPAS label.
     *
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param $label
     */
    public function addLabel(
        \CultureFeed_Cdb_Item_Event $event,
        $label
    );

    /**
     * Remove the given label from the event, only when UiTPAS label.
     *
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param $label
     */
    public function removeLabel(
        \CultureFeed_Cdb_Item_Event $event,
        $label
    );
}
