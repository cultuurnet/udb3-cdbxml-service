<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

use CultureFeed_Cdb_Xml;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;

class CdbXmlDocumentParser implements CdbXmlDocumentParserInterface
{
    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @param string $version
     * @return SimpleXMLElement
     *
     * @throws InvalidArgumentException
     *   When the cdbxml could not be parsed.
     */
    public function parse(CdbXmlDocument $cdbXmlDocument, $version = '3.3')
    {
        $cdbXml = $cdbXmlDocument->getCdbXml();
        $namespaceUri = CultureFeed_Cdb_Xml::namespaceUriForVersion($version);

        try {
            return new SimpleXMLElement($cdbXml, 0, false, $namespaceUri);
        } catch (Exception $e) {
            throw new InvalidArgumentException('CdbXml could not be parsed.');
        }
    }
}
