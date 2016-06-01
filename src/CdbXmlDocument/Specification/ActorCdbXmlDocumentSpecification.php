<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;

class ActorCdbXmlDocumentSpecification extends RootElementCdbXmlDocumentSpecification
{
    /**
     * @param CdbXmlDocumentParserInterface $parser
     */
    public function __construct(CdbXmlDocumentParserInterface $parser)
    {
        parent::__construct($parser, 'actor');
    }
}
