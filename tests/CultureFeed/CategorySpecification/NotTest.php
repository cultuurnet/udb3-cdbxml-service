<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class NotTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategorySpecificationInterface
     */
    private $categorySpecification;

    /**
     * @var Not
     */
    private $not;

    protected function setUp()
    {
        $this->categorySpecification = $this->createMock(
            CategorySpecificationInterface::class
        );

        $this->categorySpecification->expects($this->once())
            ->method('matches')
            ->willReturn(true);

        $this->not = new Not($this->categorySpecification);
    }

    /**
     * @test
     */
    public function it_can_negate_a_category_specification()
    {
        $category = new CultureFeed_Cdb_Data_Category(
            'eventtype',
            '0.50.4.0.0',
            'concert'
        );

        $this->assertFalse($this->not->matches($category));
    }
}
