<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

interface CategorySpecificationInterface
{
    public function matches(CultureFeed_Cdb_Data_Category $category);
}