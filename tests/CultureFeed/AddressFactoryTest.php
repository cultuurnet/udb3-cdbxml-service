<?php

namespace CultuurNet\UDB3\CdbXmlService\CultureFeed;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class AddressFactoryTest extends TestCase
{
    /**
     * @var AddressFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new AddressFactory();
    }

    /**
     * @test
     * @dataProvider addressDataProvider
     *
     * @param $udb3Address
     * @param $expectedCulturefeedAddress
     */
    public function it_can_create_culturefeed_address_from_udb3_addresses(
        $udb3Address,
        $expectedCulturefeedAddress
    ) {
        $this->assertEquals(
            $expectedCulturefeedAddress,
            $this->factory->fromUdb3Address($udb3Address)
        );
    }

    /**
     * @return array
     */
    public function addressDataProvider()
    {
        $data = [];

        // Single-word street name + house number.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Bondgenotenlaan');
        $physicalAddress->setHouseNumber('1');
        $physicalAddress->setZip('3000');
        $physicalAddress->setCity('Leuven');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('Bondgenotenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        // Single-word street name without house number.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Bondgenotenlaan');
        $physicalAddress->setZip('3000');
        $physicalAddress->setCity('Leuven');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('Bondgenotenlaan'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        // Single-word street name with house number and post box.
        // This is known to be incorrect, but we have no way to fix this for
        // now.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Bondgenotenlaan 1');
        $physicalAddress->setHouseNumber('03.01');
        $physicalAddress->setZip('3000');
        $physicalAddress->setCity('Leuven');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('Bondgenotenlaan 1 03.01'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        // Multi-word street name + house number.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Maarschalk Gerardstraat');
        $physicalAddress->setHouseNumber('4');
        $physicalAddress->setZip('2000');
        $physicalAddress->setCity('Antwerpen');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('Maarschalk Gerardstraat 4'),
                new PostalCode('2000'),
                new Locality('Antwerpen'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        // Multi-word street name without house number.
        // This is known to be incorrect, but we have no way to fix this for
        // now.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Maarschalk');
        $physicalAddress->setHouseNumber('Gerardstraat');
        $physicalAddress->setZip('2000');
        $physicalAddress->setCity('Antwerpen');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('Maarschalk Gerardstraat'),
                new PostalCode('2000'),
                new Locality('Antwerpen'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        // Multi-word street name with house number and post box.
        // This is known to be incorrect, but we have no way to fix this for
        // now.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('Maarschalk Gerardstraat 4');
        $physicalAddress->setHouseNumber('03.01');
        $physicalAddress->setZip('2000');
        $physicalAddress->setCity('Antwerpen');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('Maarschalk Gerardstraat 4 03.01'),
                new PostalCode('2000'),
                new Locality('Antwerpen'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        // Street name with ampserand in it.
        $physicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $physicalAddress->setStreet('j & m Sabbestraat');
        $physicalAddress->setHouseNumber('163a');
        $physicalAddress->setZip('8930');
        $physicalAddress->setCity('Menen');
        $physicalAddress->setCountry('BE');

        $data[] = [
            new Address(
                new Street('j & m Sabbestraat 163a'),
                new PostalCode('8930'),
                new Locality('Menen'),
                Country::fromNative('BE')
            ),
            new \CultureFeed_Cdb_Data_Address(
                $physicalAddress
            ),
        ];

        return $data;
    }
}
