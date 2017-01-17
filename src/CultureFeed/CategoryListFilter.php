<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultureFeed_Cdb_Data_CategoryList;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\CategorySpecificationInterface;

class CategoryListFilter
{
    /**
     * @var CategorySpecificationInterface
     */
    private $spec;

    /**
     * @param CategorySpecificationInterface $spec
     */
    public function __construct(CategorySpecificationInterface $spec)
    {
        $this->spec = $spec;
    }

    /**
     * @param CultureFeed_Cdb_Data_CategoryList $categories
     * @return CultureFeed_Cdb_Data_CategoryList
     */
    public function filter(CultureFeed_Cdb_Data_CategoryList $categories)
    {
        $matchingCategories = new CultureFeed_Cdb_Data_CategoryList();

        foreach ($categories as $category) {
            if ($this->spec->matches($category)) {
                $matchingCategories->add($category);
            }
        }

        return $matchingCategories;
    }
}
