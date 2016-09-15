<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;

class OfferDocumentMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OfferDocumentMetadataFactory
     */
    protected $offerDocumentMetadataFactory;

    public function setUp()
    {
        $this->offerDocumentMetadataFactory = new OfferDocumentMetadataFactory();
    }

    /**
     * @test
     */
    public function it_returns_the_correct_metadata_when_given_an_event_cdbxml()
    {
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            file_get_contents(__DIR__ . '/Repository/samples/' . 'event.xml')
        );

        $metadata = $this->offerDocumentMetadataFactory->createMetadata($cdbXmlDocument);

        $expectedMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_mail' => 'foo@bar.com',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
                'request_time' => '1460710907',
            ]
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    /**
     * @test
     */
    public function it_returns_the_correct_metadata_when_given_a_place_cdbxml()
    {
        $id = '34973B89-BDA3-4A79-96C7-78ACC022907D';

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            file_get_contents(__DIR__ . '/Repository/samples/' . 'place.xml')
        );

        $metadata = $this->offerDocumentMetadataFactory->createMetadata($cdbXmlDocument);

        $expectedMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_mail' => 'foo@bar.com',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
                'request_time' => '1460710907',
            ]
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }

    /**
     * @test
     */
    public function it_returns_the_correct_metadata_when_given_an_organizer_cdbxml()
    {
        $id = 'ORG-123';

        $cdbXmlDocument = new CdbXmlDocument(
            $id,
            file_get_contents(__DIR__ . '/Repository/samples/' . 'actor.xml')
        );

        $metadata = $this->offerDocumentMetadataFactory->createMetadata($cdbXmlDocument);

        $expectedMetadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_mail' => 'foo@bar.com',
                'id' => 'http://foo.be/item/ORG-123',
                'request_time' => '1460710907',
            ]
        );

        $this->assertEquals($expectedMetadata, $metadata);
    }
}
