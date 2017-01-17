<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification;

use CultureFeed_Cdb_Data_Category;

class AnyOffTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategorySpecificationInterface
     */
    private $trueCategorySpecification;

    /**
     * @var CategorySpecificationInterface
     */
    private $falseCategorySpecification;

    /**
     * @var AnyOff
     */
    private $anyOff;

    protected function setUp()
    {
        $this->falseCategorySpecification = $this->createCategorySpecification(false);

        $this->trueCategorySpecification = $this->createCategorySpecification(true);

        $this->anyOff = new AnyOff(
            $this->falseCategorySpecification,
            $this->trueCategorySpecification
        );
    }

    /**
     * @test
     */
    public function it_matches_when_at_least_one_specification_matches()
    {
        $category = new CultureFeed_Cdb_Data_Category(
            'eventtype',
            '0.50.4.0.0',
            'concert'
        );

        $this->assertTrue($this->anyOff->matches($category));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_parameter_has_wrong_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid argument received at position 2, expected an implementation of CategorySpecificationInterface'
        );

        $this->anyOff = new AnyOff(
            $this->falseCategorySpecification,
            $this->trueCategorySpecification,
            'I am a string'
        );
    }

    /**
     * @param bool $result
     * @return CategorySpecificationInterface
     */
    private function createCategorySpecification($result)
    {
        /** @var CategorySpecificationInterface|\PHPUnit_Framework_MockObject_MockObject $categorySpecification */
        $categorySpecification = $this->createMock(
            CategorySpecificationInterface::class
        );

        $categorySpecification
            ->method('matches')
            ->willReturn($result);

        return $categorySpecification;
    }
}
