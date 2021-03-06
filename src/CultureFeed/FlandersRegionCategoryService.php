<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\Not;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\CategorySpecification\Type;
use SimpleXMLElement;

class FlandersRegionCategoryService implements FlandersRegionCategoryServiceInterface
{
    /**
     * @var SimpleXMLElement
     */
    protected $terms;

    /**
     * @param string $xml
     */
    public function __construct($xml)
    {
        $this->terms = new SimpleXMLElement($xml);
    }

    /**
     * @param CultureFeed_Cdb_Data_Address_PhysicalAddress $physicalAddress
     *
     * @return CultureFeed_Cdb_Data_Category|null
     */
    public function findFlandersRegionCategory(CultureFeed_Cdb_Data_Address_PhysicalAddress $physicalAddress)
    {
        $flandersRegion = CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION;
        $city = strtolower($physicalAddress->getCity());
        $zip = $physicalAddress->getZip();
        $category = null;

        $this->terms->registerXPathNamespace('c', 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL');

        // The following code tries to search for the city label in a case-insenstive way.
        // xpath 2.0 has the method lower-case to convert a string to lower casing.
        // Unfortunate PHP only supports xpath 1.0. A work-around for xpath 1.0 is to use translate.
        // This approach only takes into account the basic characters and not yet special characters.
        // Also make sure to surround the search terms with double quotes to allow searching on single quotes.
        $result = $this->terms->xpath(
            '//c:term[
                @domain="' . $flandersRegion . '" and 
                contains(@label, "' . $zip . '") and 
                contains(
                    translate(@label, "ABCDEFGHJIKLMNOPQRSTUVWXYZ", "abcdefghjiklmnopqrstuvwxyz"),
                    "' . $city . '"
                )
            ]'
        );

        if (count($result)) {
            $category = new CultureFeed_Cdb_Data_Category(
                CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION,
                (string) $result[0]['id'],
                (string) $result[0]['label']
            );
        }

        return $category;
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param CultureFeed_Cdb_Data_Category|null $newCategory
     */
    public function updateFlandersRegionCategories(
        CultureFeed_Cdb_Item_Base $item,
        CultureFeed_Cdb_Data_Category $newCategory = null
    ) {
        $filter = new CategoryListFilter(
            new Not(new Type('flandersregion'))
        );

        $categories = $filter->filter($item->getCategories());

        if ($newCategory) {
            $categories->add($newCategory);
        }

        $item->setCategories($categories);
    }
}
