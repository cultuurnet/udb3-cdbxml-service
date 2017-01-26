<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class ID implements CategorySpecificationInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * ID constructor.
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function matches(CultureFeed_Cdb_Data_Category $category)
    {
        return $category->getId() === $this->id;
    }
}
