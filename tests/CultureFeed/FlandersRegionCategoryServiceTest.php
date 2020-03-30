<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultureFeed_Cdb_Data_Category;
use PHPUnit\Framework\TestCase;

class FlandersRegionCategoryServiceTest extends TestCase
{
    /**
     * @var FlandersRegionCategoryService
     */
    protected $categories;

    protected function setUp(): void
    {
        $xml = file_get_contents(__DIR__ . '/samples/flanders_region_terms.xml');
        $this->categories = new FlandersRegionCategoryService($xml);
    }

    /**
     * @test
     */
    public function it_returns_a_category_by_city_and_zip()
    {
        $address = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $address->setCity('Oud-Heverlee');
        $address->setZip('3050');

        $expectedCategory = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.651', '3050 Oud-Heverlee');

        $actualCategory = $this->categories->findFlandersRegionCategory($address);
        $this->assertEquals($expectedCategory, $actualCategory);
    }

    /**
     * @test
     */
    public function it_returns_a_category_case_insensitive_by_city_and_zip()
    {
        $address = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $address->setCity('Heist-op-den-Berg');
        $address->setZip('2220');

        $expectedCategory = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.500', '2220 Heist-Op-Den-Berg');

        $actualCategory = $this->categories->findFlandersRegionCategory($address);
        $this->assertEquals($expectedCategory, $actualCategory);
    }

    /**
     * @test
     */
    public function it_returns_a_category_case_insensitive_with_qoutes_by_city_and_zip()
    {
        $address = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $address->setCity('Sint-Job-In-\'t-Goor');
        $address->setZip('2960');

        $expectedCategory = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.630', '2960 Sint-Job-In-\'t-Goor (Brecht)');

        $actualCategory = $this->categories->findFlandersRegionCategory($address);
        $this->assertEquals($expectedCategory, $actualCategory);
    }

    /**
     * @test
     */
    public function it_updates_an_existing_category()
    {
        $category = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.651', '3050 Oud-Heverlee');
        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $categoryList->add($category);

        $item = new \CultureFeed_Cdb_Item_Event();
        $item->setCategories($categoryList);

        $category = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.643', '3020 Herent');

        $this->categories->updateFlandersRegionCategories($item, $category);
        $actualCategoryList = $item->getCategories();
        $this->assertTrue($actualCategoryList->hasCategory('reg.643'));
        $this->assertFalse($actualCategoryList->hasCategory('reg.651'));
    }

    /**
     * @test
     */
    public function it_removes_an_existing_category_when_no_category_is_provided()
    {
        $category = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.651', '3050 Oud-Heverlee');
        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $categoryList->add($category);

        $item = new \CultureFeed_Cdb_Item_Event();
        $item->setCategories($categoryList);

        $this->categories->updateFlandersRegionCategories($item, null);
        $actualCategoryList = $item->getCategories();
        $actualCategories = $actualCategoryList->getCategoriesByType(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION);
        $this->assertEmpty($actualCategories);
    }

    /**
     * @test
     */
    public function it_adds_a_category()
    {
        $item = new \CultureFeed_Cdb_Item_Event();
        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $item->setCategories($categoryList);

        $category = new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION, 'reg.651', '3050 Oud-Heverlee');

        $this->categories->updateFlandersRegionCategories($item, $category);
        $actualCategoryList = $item->getCategories();
        $this->assertTrue($actualCategoryList->hasCategory('reg.651'));
    }
}
