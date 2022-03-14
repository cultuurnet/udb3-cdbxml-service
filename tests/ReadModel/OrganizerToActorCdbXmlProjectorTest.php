<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;

class OrganizerToActorCdbXmlProjectorTest extends CdbXmlProjectorTestBase
{
    /**
     * @var OrganizerToActorCdbXmlProjector
     */
    protected $projector;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var Metadata
     */
    private $updateMetadata;

    public function setUp()
    {
        parent::setUp();
        $this->setCdbXmlFilesPath(__DIR__ . '/Repository/samples/');

        date_default_timezone_set('Europe/Brussels');

        $this->projector = (
            new OrganizerToActorCdbXmlProjector(
                $this->repository,
                new CdbXmlDocumentFactory('3.3'),
                new AddressFactory(),
                new MetadataCdbItemEnricher(
                    new CdbXmlDateFormatter()
                )
            )
        );

        $this->metadata = new Metadata(
            [
                'user_nick' => 'foobar',
                'user_email' => 'foo@bar.com',
                'user_id' => '96fd6c13-eaab-4dd1-bb6a-1c483d5e40aa',
                'request_time' => '1460710907',
                'id' => 'http://foo.be/item/ORG-123',
            ]
        );

        $this->updateMetadata = new Metadata(
            [
                'user_nick' => 'foobaz',
                'user_email' => 'foo@acme.com',
                'user_id' => '165c4a43-635e-49f7-bda7-ba7da44a0bd4',
                'request_time' => '1476781256',
                'id' => 'http://foo.be/item/ORG-123',
            ]
        );
    }

    /**
     * @test
     */
    public function it_projects_organizer_created()
    {
        $id = 'ORG-123';

        $event = new OrganizerCreated(
            $id,
            new Title('DE Studio'),
            [
                new Address(
                    new Street('Maarschalk Gerardstraat 4'),
                    new PostalCode('2000'),
                    new Locality('Antwerpen'),
                    Country::fromNative('BE')
                ),
            ],
            ['+32 3 260 96 10'],
            ['info@villanella.be'],
            ['http://www.destudio.com']
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_created_with_unique_website()
    {
        $id = 'ORG-123';

        $event = new OrganizerCreatedWithUniqueWebsite(
            $id,
            new Language('nl'),
            'http://www.destudio.com',
            new Title('DE Studio')
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-unique-website.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_created_with_unique_website_and_english_main_language()
    {
        $id = 'ORG-123';

        $event = new OrganizerCreatedWithUniqueWebsite(
            $id,
            new Language('en'),
            'http://www.destudio.com',
            new Title('DE Studio')
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-unique-website-and-english-main-language.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_created_with_unique__website_and_slash_in_querystring()
    {
        $id = 'ORG-123';

        $event = new OrganizerCreatedWithUniqueWebsite(
            $id,
            new Language('en'),
            'https://www.bravenewbooks.nl/site/?r=userwebsite/index&id=arnobraet',
            new Title('DE Studio')
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-unique-website-and-slash-in-querystring.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_address_updated_and_updates_the_lastupdated_attributes()
    {
        $id = 'ORG-123';

        $event = new AddressUpdated(
            $id,
            new Address(
                new Street('Martelarenplein 12'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            )
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->updateMetadata);

        $initialDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->repository->save($initialDocument);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-updated-address.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }


    /**
     * @test
     */
    public function it_projects_address_updated_removed()
    {
        $id = 'ORG-123';

        $event = new AddressRemoved(
            $id
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->updateMetadata);

        $initialDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->repository->save($initialDocument);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-address-removed.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_contact_point_updated_and_updates_the_lastupdated_attributes()
    {
        $id = 'ORG-123';

        $event = new ContactPointUpdated(
            $id,
            new ContactPoint(
                ['+32 444 56 56 56'],
                ['info@acme.com'],
                ['http://acme.com']
            )
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->updateMetadata);

        $initialDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->repository->save($initialDocument);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor-with-updated-contact-point.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_imported_from_udb2()
    {
        $id = 'ORG-123';

        $event = new OrganizerImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('actor-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_projects_organizer_updated_from_udb2()
    {
        $id = 'ORG-123';

        $event = new OrganizerUpdatedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('actor-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage($id, $event, $this->metadata);

        $expectedCdbXmlDocument = new CdbXmlDocument(
            $id,
            $this->loadCdbXmlFromFile('actor.xml')
        );

        $this->projector->handle($domainMessage);

        $this->assertCdbXmlDocumentInRepository($expectedCdbXmlDocument);
    }

    /**
     * @test
     */
    public function it_handles_website_updated()
    {
        $organizerId = 'ORG-123';
        $titleUpdated = new WebsiteUpdated(
            $organizerId,
            'http://www.destudio.com'
        );

        $domainMessage = $this->createDomainMessage(
            $organizerId,
            $titleUpdated,
            $this->metadata
        );

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-unique-website.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_title_updated()
    {
        $organizerId = 'ORG-123';
        $titleUpdated = new TitleUpdated(
            $organizerId,
            new Title('Het Depot')
        );

        $domainMessage = $this->createDomainMessage(
            $organizerId,
            $titleUpdated,
            $this->metadata
        );

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-updated-title.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_title_translated()
    {
        $organizerId = 'ORG-123';
        $titleUpdated = new TitleTranslated(
            $organizerId,
            new Title('LE Studio'),
            new Language('fr')
        );

        $domainMessage = $this->createDomainMessage(
            $organizerId,
            $titleUpdated,
            $this->metadata
        );

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-translated-title.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_title_translated_of_already_translated_title()
    {
        $organizerId = 'ORG-123';
        $titleUpdated = new TitleTranslated(
            $organizerId,
            new Title('DE Studio FR'),
            new Language('fr')
        );

        $domainMessage = $this->createDomainMessage(
            $organizerId,
            $titleUpdated,
            $this->metadata
        );

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-translated-title.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-modified-translated-title.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_label_added()
    {
        $organizerId = 'ORG-123';
        $labelAdded = new LabelAdded($organizerId, new Label('2dotstwice'));

        $domainMessage = $this->createDomainMessage($organizerId, $labelAdded, $this->metadata);

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info-and-label.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_invisible_label_added()
    {
        $organizerId = 'ORG-123';
        $labelAdded = new LabelAdded($organizerId, new Label('2dotstwice', false));

        $domainMessage = $this->createDomainMessage($organizerId, $labelAdded, $this->metadata);

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info-and-label-invisible.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_label_removed()
    {
        $organizerId = 'ORG-123';
        $labelRemoved = new LabelRemoved($organizerId, new Label('2dotstwice'));

        $domainMessage = $this->createDomainMessage($organizerId, $labelRemoved, $this->metadata);

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info-and-label.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }

    /**
     * @test
     */
    public function it_handles_invisible_label_removed()
    {
        $organizerId = 'ORG-123';
        $labelRemoved = new LabelRemoved($organizerId, new Label('2dotstwice', false));

        $domainMessage = $this->createDomainMessage($organizerId, $labelRemoved, $this->metadata);

        $document = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info-and-label-invisible.xml')
        );
        $this->repository->save($document);

        $this->projector->handle($domainMessage);

        $expectedDocument = new CdbXmlDocument(
            $organizerId,
            $this->loadCdbXmlFromFile('actor-with-contact-info.xml')
        );
        $this->assertCdbXmlDocumentInRepository($expectedDocument);
    }
}
