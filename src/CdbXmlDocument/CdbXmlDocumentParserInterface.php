<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

use InvalidArgumentException;
use SimpleXMLElement;

interface CdbXmlDocumentParserInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @param string $version
     * @return SimpleXMLElement
     *
     * @throws InvalidArgumentException
     *   When the cdbxml could not be parsed.
     */
    public function parse(CdbXmlDocument $cdbXmlDocument, $version = '3.3');
}
