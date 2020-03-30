<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

use PHPUnit\Framework\TestCase;

class CdbXmlDocumentFactoryTest extends TestCase
{
    /**
     * @var CdbXmlDocumentFactory
     */
    private $factory;

    protected function setUp(): void
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
        $actor->setCdbId('ORG-123');

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

        $actor->setCreatedBy('96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa');
        $actor->setLastUpdatedBy('96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa');
        $actor->setCreationDate('2016-04-15T11:01:47');
        $actor->setLastUpdated('2016-04-15T11:01:47');
        $actor->setExternalUrl('http://foo.be/item/ORG-123');

        $data[] = [
            $actor,
            new CdbXmlDocument(
                'ORG-123',
                file_get_contents(__DIR__ . '/../ReadModel/Repository/samples/actor.xml')
            ),
        ];

        return $data;
    }
}
