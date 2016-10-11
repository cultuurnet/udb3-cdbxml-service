<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\Organizer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;

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
    public function it_projects_organizer_imported_from_udb2()
    {
        $id = 'ORG-123';

        $event = new OrganizerImportedFromUDB2(
            $id,
            $this->loadCdbXmlFromFile('actor-namespaced.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $domainMessage = $this->createDomainMessage($id, $event);

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

        $domainMessage = $this->createDomainMessage($id, $event);

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
    public function it_handles_label_added()
    {
        $organizerId = 'ORG-123';
        $labelId = new UUID();
        $labelAdded = new LabelAdded($organizerId, $labelId);

        $domainMessage = $this->createDomainMessage($organizerId, $labelAdded);
        $domainMessage = $domainMessage->andMetadata(
            new Metadata(['labelName' => '2dotstwice'])
        );

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
    public function it_handles_label_removed()
    {
        $organizerId = 'ORG-123';
        $labelId = new UUID();
        $labelRemoved = new LabelRemoved($organizerId, $labelId);

        $domainMessage = $this->createDomainMessage($organizerId, $labelRemoved);
        $domainMessage = $domainMessage->andMetadata(
            new Metadata(['labelName' => '2dotstwice'])
        );

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
}
