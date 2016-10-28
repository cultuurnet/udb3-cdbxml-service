<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

interface LabelApplierInterface
{
    /**
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param \CultureFeed_Cdb_Item_Actor $actor
     */
    public function addLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    );

    /**
     * @param \CultureFeed_Cdb_Item_Event $event
     * @param \CultureFeed_Cdb_Item_Actor $actor
     */
    public function removeLabels(
        \CultureFeed_Cdb_Item_Event $event,
        \CultureFeed_Cdb_Item_Actor $actor
    );
}
