<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Item_Base;
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
        $city = $physicalAddress->getCity();
        $zip = $physicalAddress->getZip();
        $category = null;

        $this->terms->registerXPathNamespace('c', 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL');
        $result = $this->terms->xpath(
            '//c:term[@domain=\'' . CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION . '\' and '
            . 'contains(@label, \'' . $zip . '\') and '
            . 'contains(@label, \'' . $city . '\')]'
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
        $oldCategories = $item->getCategories();
        $newCategories = new \CultureFeed_Cdb_Data_CategoryList();
        $themeMissing = true;

        foreach ($oldCategories as $index => $category) {
            if ($category->getType() == CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION) {
                $themeMissing = false;
                if ($newCategory) {
                    $newCategories->add($newCategory);
                }
            } else {
                $newCategories->add($category);
            }
        }

        if ($themeMissing && $newCategory) {
            $newCategories->add($newCategory);
        }

        $item->setCategories($newCategories);
    }
}
