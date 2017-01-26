<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class AnyOff implements CategorySpecificationInterface
{
    /**
     * @var CategorySpecificationInterface[]
     */
    private $wrapped;

    /**
     * AnyOff constructor.
     * @param CategorySpecificationInterface[] ...$specs
     */
    public function __construct(CategorySpecificationInterface ...$specs)
    {
        $this->wrapped = $specs;
    }

    /**
     * @inheritdoc
     */
    public function matches(CultureFeed_Cdb_Data_Category $category)
    {
        foreach ($this->wrapped as $wrapped) {
            if ($wrapped->matches($category)) {
                return true;
            }
        }

        return false;
    }
}
