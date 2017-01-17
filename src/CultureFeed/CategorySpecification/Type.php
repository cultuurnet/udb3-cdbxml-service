<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class Type implements CategorySpecificationInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function matches(CultureFeed_Cdb_Data_Category $category)
    {
        return $category->getType() == $this->type;
    }
}
