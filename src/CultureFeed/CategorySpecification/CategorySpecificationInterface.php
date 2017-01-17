<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

interface CategorySpecificationInterface
{
    /**
     * @param CultureFeed_Cdb_Data_Category $category
     * @return bool
     */
    public function matches(CultureFeed_Cdb_Data_Category $category);
}
