<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class AnyOff implements CategorySpecificationInterface
{
    /**
     * @var CategorySpecificationInterface[]
     */
    private $wrapped;

    public function __construct()
    {
        $args = func_get_args();

        foreach ($args as $index => $arg) {
            if (!$arg instanceof CategorySpecificationInterface) {
                throw new \InvalidArgumentException(
                    "Invalid argument received at position $index, expected an implementation of CategorySpecificationInterface"
                );
            }
        }

        $this->wrapped = $args;
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
