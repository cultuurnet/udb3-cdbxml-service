<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEventWithIri;

abstract class AbstractOfferProjectedToCdbXml extends AbstractEventWithIri
{
    /**
     * @var bool
     */
    protected $isNew;

    /**
     * AbstractOfferProjectedToCdbXml constructor.
     * @param string $itemId
     * @param string $iri
     * @param bool $isNew
     */
    public function __construct($itemId, $iri, $isNew = false)
    {
        parent::__construct($itemId, $iri);
        $this->isNew = $isNew;
    }

    public function isNew()
    {
        return $this->isNew;
    }
}
