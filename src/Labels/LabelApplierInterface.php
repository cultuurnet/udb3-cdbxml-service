<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;

interface LabelApplierInterface
{
    /**
     * Add only the UiTPAS labels from the provided organizer to the provided event.
     *
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param LabelCollection $labelCollection
     * @return \CultureFeed_Cdb_Item_Event $event
     */
    public function addLabels(
        \CultureFeed_Cdb_Item_Event $event,
        LabelCollection $labelCollection
    );

    /**
     * Remove only the UiTPAS labels from the provided organizer to the provided event.
     *
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param LabelCollection $labelCollection
     * @return \CultureFeed_Cdb_Item_Event $event
     */
    public function removeLabels(
        \CultureFeed_Cdb_Item_Event $event,
        LabelCollection $labelCollection
    );
}
