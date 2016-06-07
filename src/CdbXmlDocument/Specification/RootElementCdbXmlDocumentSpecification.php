<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;

class RootElementCdbXmlDocumentSpecification implements CdbXmlDocumentSpecificationInterface
{
    /**
     * @var CdbXmlDocumentParserInterface
     */
    private $parser;

    /**
     * @var string
     */
    private $rootElementName;

    /**
     * @param CdbXmlDocumentParserInterface $parser
     * @param string $rootElementName
     */
    public function __construct(CdbXmlDocumentParserInterface $parser, $rootElementName)
    {
        $this->parser = $parser;
        $this->rootElementName = $rootElementName;
    }

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return bool
     */
    public function isSatisfiedBy(CdbXmlDocument $cdbXmlDocument)
    {
        $simpleXmlElement = $this->parser->parse($cdbXmlDocument);
        return $simpleXmlElement->getName() == 'cdbxml' && isset($simpleXmlElement->{$this->rootElementName});
    }
}
