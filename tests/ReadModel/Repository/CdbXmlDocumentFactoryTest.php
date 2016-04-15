<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

class CdbXmlDocumentFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CdbXmlDocumentFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new CdbXmlDocumentFactory('3.3');
    }

    /**
     * @test
     * @dataProvider cdbXmlDataProvider
     *
     * @param $cfCdbItem
     * @param $expectedCdbXmlDocument
     */
    public function it_creates_a_cdbxml_document_based_on_a_culturefeed_cdb_item(
        $cfCdbItem,
        $expectedCdbXmlDocument
    ) {
        $this->assertEquals(
            $expectedCdbXmlDocument,
            $this->factory->fromCulturefeedCdbItem($cfCdbItem)
        );
    }

    /**
     * @return array
     */
    public function cdbXmlDataProvider()
    {
        $data = [];

        // Actor.
        $actor = new \CultureFeed_Cdb_Item_Actor();
        $actor->setCdbId('404EE8DE-E828-9C07-FE7D12DC4EB24480');

        $nlDetail = new \CultureFeed_Cdb_Data_ActorDetail();
        $nlDetail->setLanguage('nl');
        $nlDetail->setTitle('DE Studio');

        $details = new \CultureFeed_Cdb_Data_ActorDetailList();
        $details->add($nlDetail);
        $actor->setDetails($details);

        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $categoryList->add(
            new \CultureFeed_Cdb_Data_Category(
                'actortype',
                '8.11.0.0.0',
                'Organisator(en)'
            )
        );
        $actor->setCategories($categoryList);

        $data[] = [
            $actor,
            new CdbXmlDocument(
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                file_get_contents(__DIR__ . '/samples/actor.xml')
            ),
        ];

        return $data;
    }
}
