<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Item_Base;

interface FlandersRegionCategoryServiceInterface
{
    /**
     * @param CultureFeed_Cdb_Data_Address_PhysicalAddress $physicalAddress
     *
     * @return CultureFeed_Cdb_Data_Category|null
     */
    public function findFlandersRegionCategory(CultureFeed_Cdb_Data_Address_PhysicalAddress $physicalAddress);

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param CultureFeed_Cdb_Data_Category|null $newCategory
     */
    public function updateFlandersRegionCategories(CultureFeed_Cdb_Item_Base $item, CultureFeed_Cdb_Data_Category $newCategory = null);
}
