<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;

class EventCdbXmlDocumentSpecification extends RootElementCdbXmlDocumentSpecification
{
    /**
     * @param CdbXmlDocumentParserInterface $parser
     */
    public function __construct(CdbXmlDocumentParserInterface $parser)
    {
        parent::__construct($parser, 'event');
    }
}
