<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Item_Base;
use SimpleXMLElement;

class FlandersRegionCategories
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
                $result[0]['id'],
                $result[0]['label']
            );
        }

        return $category;
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param CultureFeed_Cdb_Data_Category|null $newCategory
     */
    public function updateFlandersRegionCategories(CultureFeed_Cdb_Item_Base $item, CultureFeed_Cdb_Data_Category $newCategory = null)
    {
        $updated = false;
        foreach ($item->getCategories() as $key => $category) {
            if ($category->getType() == CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION) {
                if ($newCategory && !$updated) {
                    $category->setId($newCategory->getId());
                    $category->setName($newCategory->getName());
                    $updated = true;
                } else {
                    $item->getCategories()->delete($key);
                }
            }
        }

        if (!$updated && $newCategory) {
            $item->getCategories()->add($newCategory);
        }
    }
}
