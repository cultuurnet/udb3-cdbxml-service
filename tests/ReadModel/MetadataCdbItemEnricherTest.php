<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;

class MetadataCdbItemEnricherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \CultureFeed_Cdb_Item_Base|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cdbItemBase;

    /**
     * @var MetadataCdbItemEnricher
     */
    private $enricher;

    /**
     * @var CdbXmlDateFormatter
     */
    private $dateFormatter;

    public function setUp()
    {
        $this->dateFormatter = new CdbXmlDateFormatter();

        $this->cdbItemBase = $this->getMockForAbstractClass(\CultureFeed_Cdb_Item_Base::class);
        $this->enricher = new MetadataCdbItemEnricher(
            $this->dateFormatter
        );
    }

    /**
     * @test
     */
    public function it_adds_created_by_if_not_set_before_and_metadata_contains_user_nick()
    {
        $nickname = 'foobar';
        $metadata = Metadata::kv('user_nick', $nickname);

        $expectedCdbItem = clone $this->cdbItemBase;
        $expectedCdbItem->setCreatedBy('foobar');

        $actualCdbItem = $this->enricher->enrich($this->cdbItemBase, $metadata);
        $this->assertEquals($expectedCdbItem, $actualCdbItem);

        // Make sure created by is never overwritten.
        $metadata = Metadata::kv('user_nick', 'different_nickname');
        $actualCdbItem = $this->enricher->enrich($actualCdbItem, $metadata);
        $this->assertEquals($expectedCdbItem, $actualCdbItem);
    }

    /**
     * @test
     */
    public function it_adds_last_updated_by_if_metadata_contains_user_email()
    {
        $email = 'foo@bar.com';
        $metadata = Metadata::kv('user_email', $email);

        $expectedCdbItem = clone $this->cdbItemBase;
        $expectedCdbItem->setLastUpdatedBy($email);

        $actualCdbItem = $this->enricher->enrich($this->cdbItemBase, $metadata);
        $this->assertEquals($expectedCdbItem, $actualCdbItem);
    }

    /**
     * @test
     */
    public function it_adds_last_updated_if_metadata_contains_request_time_and_it_adds_creation_date_if_not_set_before()
    {
        $requestTime = '1460710907';
        $metadata = Metadata::kv('request_time', $requestTime);

        $expectedCdbItem = clone $this->cdbItemBase;
        $expectedCdbItem->setCreationDate('2016-04-15T11:01:47');
        $expectedCdbItem->setLastUpdated('2016-04-15T11:01:47');

        $actualCdbItem = $this->enricher->enrich($this->cdbItemBase, $metadata);
        $this->assertEquals($expectedCdbItem, $actualCdbItem);

        // Make sure the creation date is never overwritten.
        $metadata = Metadata::kv('request_time', '1554852157');
        $expectedCdbItem->setLastUpdated('2019-04-10T01:22:37');
        $actualCdbItem = $this->enricher->enrich($actualCdbItem, $metadata);
        $this->assertEquals($expectedCdbItem, $actualCdbItem);
    }
}
