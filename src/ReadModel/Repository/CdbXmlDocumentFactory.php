<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

class CdbXmlDocumentFactory implements CdbXmlDocumentFactoryInterface
{
    /**
     * @var string
     */
    private $schemaVersion;

    /**
     * @param string $schemaVersion
     */
    public function __construct($schemaVersion)
    {
        $this->schemaVersion = (string) $schemaVersion;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Base $cdbItem
     * @return CdbXmlDocument
     */
    public function fromCulturefeedCdbItem(\CultureFeed_Cdb_Item_Base $cdbItem)
    {
        $id = $cdbItem->getCdbId();

        $cdbXml = new \CultureFeed_Cdb_Default($this->schemaVersion);
        $cdbXml->addItem($cdbItem);
        $cdbXml = (string) $cdbXml;

        return new CdbXmlDocument($id, $cdbXml);
    }
}
