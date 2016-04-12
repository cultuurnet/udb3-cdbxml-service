<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultuurNet\UDB3\Address;

interface AddressFactoryInterface
{
    /**
     * @param Address $address
     * @return \CultureFeed_Cdb_Data_Address
     */
    public function fromUdb3Address(Address $address);
}
