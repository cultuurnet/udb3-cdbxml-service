<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;
use PHPUnit\Framework\TestCase;

class IDTest extends TestCase
{
    /**
     * @var CultureFeed_Cdb_Data_Category
     */
    private $category;

    protected function setUp(): void
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
    public function it_matches_the_exact_id()
    {
        $id = new ID('0.50.4.0.0');

        $this->assertTrue($id->matches($this->category));
    }

    /**
     * @test
     */
    public function it_does_not_match_other_ids()
    {
        $id = new Type('0.50.4.0.1');

        $this->assertFalse($id->matches($this->category));
    }
}
