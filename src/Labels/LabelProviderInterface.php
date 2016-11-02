<?php

namespace CultuurNet\UDB3\CdbXmlService\Labels;

use CultuurNet\UDB3\LabelCollection;

interface LabelProviderInterface
{
    /**
     * @return LabelCollection
     */
    public function getAll();
}
