<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

interface DocumentMetadataFactoryInterface
{
    /**
     * @param CdbXmlDocument $document
     * @return Metadata
     */
    public function createMetadata(CdbXmlDocument $document);
}
