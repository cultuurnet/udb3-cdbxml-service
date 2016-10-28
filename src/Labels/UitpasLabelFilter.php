<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

class UitpasLabelFilter implements LabelFilterInterface
{
    /**
     * @var LabelProviderInterface
     */
    private $uitpasLabelProvider;

    /**
     * @var string[]
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
    public function filter(array $labels)
    {
        return array_values(array_intersect($labels, $this->uitpasLabels));
    }
}
