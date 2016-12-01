<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\StringFilter\NewlineToBreakTagStringFilter;

class LongDescriptionFilter extends NewlineToBreakTagStringFilter
{
    public function __construct()
    {
        $this->closeTag(false);
    }
}
