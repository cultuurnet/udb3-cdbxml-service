<?php

namespace CultuurNet\UDB3\CdbXmlService\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractOfferProjectedToCdbXml extends AbstractEvent
{
    /**
     * @var bool
     */
    protected $isNew;

    /**
     * @var string
     */
    private $iri;

    final public function __construct(string $itemId, string $iri, ?bool $isNew = false)
    {
        parent::__construct($itemId);
        $this->iri = $iri;
        $this->isNew = $isNew;
    }

    public function getIri(): string
    {
        return $this->iri;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'iri' => $this->iri,
            'is_new' => $this->isNew,
        );
    }

    public static function deserialize(array $data): AbstractOfferProjectedToCdbXml
    {
        return new static($data['item_id'], $data['iri'], $data['is_new']);
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }
}
