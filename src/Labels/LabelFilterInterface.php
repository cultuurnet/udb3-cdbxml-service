<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

interface LabelFilterInterface
{
    /**
     * @param string[] $labels
     * @return string[]
     */
    public function filter(array $labels);
}
