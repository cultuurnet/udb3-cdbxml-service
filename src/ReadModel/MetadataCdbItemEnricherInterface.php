<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use ValueObjects\Number\Natural;

interface MetadataCdbItemEnricherInterface
{
    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param Metadata $metadata
     * @return \CultureFeed_Cdb_Item_Base
     */
    public function enrich(\CultureFeed_Cdb_Item_Base $cdbItem, Metadata $metadata);

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @param Natural $requestTime
     * @return mixed
     */
    public function enrichTime(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        Natural $requestTime
    );
}
