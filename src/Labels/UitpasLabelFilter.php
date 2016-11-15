<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;

class UitpasLabelFilter implements LabelFilterInterface
{
    /**
     * @var LabelProviderInterface
     */
    private $uitpasLabelProvider;

    /**
     * @var LabelCollection
     */
    private $uitpasLabels;

    /**
     * UitpasLabelFilter constructor.
     * @param LabelProviderInterface $uitpasLabelProvider
     */
    public function __construct(LabelProviderInterface $uitpasLabelProvider)
    {
        $this->uitpasLabelProvider = $uitpasLabelProvider;
        $this->uitpasLabels = $this->uitpasLabelProvider->getAll();
    }

    /**
     * @inheritdoc
     */
    public function filter(LabelCollection $labels)
    {
        return $this->uitpasLabels->intersect($labels);
    }
}
