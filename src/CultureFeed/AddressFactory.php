<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultuurNet\UDB3\Address\Address;

class AddressFactory implements AddressFactoryInterface
{
    /**
     * @param Address $address
     * @return \CultureFeed_Cdb_Data_Address
     */
    public function fromUdb3Address(Address $address)
    {
        // Taken from Udb2UtilityTrait in udb3-udb2-bridge.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();

        $physicalAddress->setCountry((string) $address->getCountry()->getCode());
        $physicalAddress->setCity((string) $address->getLocality());
        $physicalAddress->setZip((string) $address->getPostalCode());

        // @todo This is not an exact mapping, because we do not have a separate
        // house number in JSONLD, this should be fixed somehow. Probably it's
        // better to use another read model than JSONLD for this purpose.
        $streetAddress = (string) $address->getStreetAddress();
        $streetParts = explode(' ', $streetAddress);

        if (count($streetParts) > 1) {
            $number = array_pop($streetParts);
            $physicalAddress->setStreet(implode(' ', $streetParts));
            $physicalAddress->setHouseNumber($number);
        } else {
            $physicalAddress->setStreet($streetAddress);
        }

        return new \CultureFeed_Cdb_Data_Address(
            $physicalAddress
        );
    }
}
