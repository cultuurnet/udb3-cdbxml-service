<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    /**
     * @var CultureFeed_Cdb_Data_Category
     */
    private $category;

    protected function setUp()
    {
        $this->category = new CultureFeed_Cdb_Data_Category(
            'eventtype',
            '0.50.4.0.0',
            'concert'
        );
    }

    /**
     * @test
     */
    public function it_matches_exact_category_type()
    {
        $type = new Type('eventtype');

        $this->assertTrue($type->matches($this->category));
    }

    /**
     * @test
     */
    public function it_does_not_match_other_category_type()
    {
        $type = new Type('theme');

        $this->assertFalse($type->matches($this->category));
    }
}
