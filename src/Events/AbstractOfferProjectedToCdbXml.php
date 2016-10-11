<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEventWithIri;

abstract class AbstractOfferProjectedToCdbXml extends AbstractEventWithIri
{
    /**
     * @var bool
     */
    protected $isNew;

    public function __construct($iri, $isNew = false)
    {
        parent::__construct($iri);
        $this->isNew = $isNew;
    }

    public function isNew()
    {
        return $this->isNew;
    }
}
