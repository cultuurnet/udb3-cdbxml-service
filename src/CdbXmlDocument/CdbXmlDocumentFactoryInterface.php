<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

interface CdbXmlDocumentFactoryInterface
{
    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @return CdbXmlDocument
     */
    public function fromCulturefeedCdbItem(\CultureFeed_Cdb_Item_Base $cdbItem);
}
