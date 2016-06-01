<?php

namespace CultuurNet\UDB3\CdbXmlService\Media;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated;
use CultuurNet\UDB3\Event\Events\MainImageSelected;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;
use ValueObjects\Web\Url;

trait EditImageTestTrait
{
    /**
     * @test
     */
    public function it_adds_a_media_file_when_adding_an_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-image.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_updates_the_event_media_object_property_when_updating_an_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bert Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $metadata);
        $this->projector->handle($domainMessage);

        $imageUpdated = new ImageUpdated(
            $id,
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new StringLiteral('Sexy ladies without clothes - NSFW'),
            new StringLiteral('Bart Ramakers')
        );

        $domainMessage = $this->createDomainMessage($id, $imageUpdated, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-image-updated.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_deletes_a_media_file_when_removing_an_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bert Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $imageRemoved = new ImageRemoved(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageRemoved, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_make_the_oldest_image_main_when_deleting_the_current_main_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $olderImage = new Image(
            new UUID('9554d6f6-bed1-4303-8d42-3fcec4601e0e'),
            new MIMEType('image/jpg'),
            new StringLiteral('Beep Boop'),
            new StringLiteral('Noo Idee'),
            Url::fromNative('http://foo.bar/media/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg')
        );

        $imageAdded = new ImageAdded(
            $id,
            $olderImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $newImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $newImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $imageRemoved = new ImageRemoved(
            $id,
            $olderImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageRemoved, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-image.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_should_update_the_image_property_when_selecting_a_main_image()
    {
        $this->createEvent();
        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';

        $metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1461162255',
                'id' => 'http://foo.be/item/404EE8DE-E828-9C07-FE7D12DC4EB24480',
            ]
        );

        $newImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new StringLiteral('sexy ladies without clothes'),
            new StringLiteral('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png')
        );

        $imageAdded = new ImageAdded(
            $id,
            $newImage
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        $image = new Image(
            new UUID('9554d6f6-bed1-4303-8d42-3fcec4601e0e'),
            new MIMEType('image/jpg'),
            new StringLiteral('Beep Boop'),
            new StringLiteral('Noo Idee'),
            Url::fromNative('http://foo.bar/media/9554d6f6-bed1-4303-8d42-3fcec4601e0e.jpg')
        );

        $imageAdded = new ImageAdded(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $imageAdded, $this->metadata);
        $this->projector->handle($domainMessage);

        // Now change the main image.
        $mainImageSelected = new MainImageSelected(
            $id,
            $image
        );

        $domainMessage = $this->createDomainMessage($id, $mainImageSelected, $metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('event-with-images.xml')
        );

        $this->expectCdbXmlDocumentToBePublished($expectedCdbXmlDocument, $domainMessage);
        $this->projector->handle($domainMessage);
        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }
}
