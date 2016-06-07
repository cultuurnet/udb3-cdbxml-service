<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

interface CdbXmlDocumentSpecificationInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return bool
     */
    public function isSatisfiedBy(CdbXmlDocument $cdbXmlDocument);
}
