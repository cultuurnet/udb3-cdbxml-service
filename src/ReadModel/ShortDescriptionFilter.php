<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\NewlineToSpaceStringFilter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;

class ShortDescriptionFilter extends CombinedStringFilter
{
    public function __construct()
    {
        $truncate = new TruncateStringFilter(400);
        $truncate->addEllipsis();
        $truncate->spaceBeforeEllipsis();
        $truncate->turnOnWordSafe(1);

        $this->addFilter(new StripHtmlStringFilter());
        $this->addFilter(new NewlineToSpaceStringFilter());
        $this->addFilter($truncate);
    }
}
