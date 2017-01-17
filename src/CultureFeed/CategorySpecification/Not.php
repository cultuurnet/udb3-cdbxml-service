<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class Not implements CategorySpecificationInterface
{
    /**
     * @var CategorySpecificationInterface
     */
    private $wrapped;

    /**
     * Not constructor.
     * @param CategorySpecificationInterface $wrapped
     */
    public function __construct(CategorySpecificationInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * @inheritdoc
     */
    public function matches(CultureFeed_Cdb_Data_Category $category)
    {
        return !$this->wrapped->matches($category);
    }
}
