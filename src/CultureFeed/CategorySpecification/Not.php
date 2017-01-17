<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class Not implements CategorySpecificationInterface
{
    /**
     * @var CategorySpecificationInterface
     */
    private $wrapped;

    public function __construct(CategorySpecificationInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function matches(CultureFeed_Cdb_Data_Category $category)
    {
        return !$this->wrapped->matches($category);
    }
}