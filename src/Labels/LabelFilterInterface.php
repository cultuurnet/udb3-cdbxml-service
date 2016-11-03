<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;

interface LabelFilterInterface
{
    /**
     * @param LabelCollection $labels
     * @return LabelCollection
     */
    public function filter(LabelCollection $labels);
}
