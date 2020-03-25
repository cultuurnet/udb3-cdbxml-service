<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Actor\ActorEvent;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\FlandersRegionCategoryService;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

class FlandersRegionOrganizerCdbXmlProjectorTest extends TestCase
{
    /**
     * @var ArrayCache
     */
    private $cache;

    /**
     * @var FlandersRegionCategoryService
     */
    private $categories;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var FlandersRegionOrganizerCdbXmlProjector
     */
    private $projector;

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/term.xml');
        $this->categories = new FlandersRegionCategoryService($xml);

        $this->projector = new FlandersRegionOrganizerCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            $this->categories
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->projector->setLogger($this->logger);
    }

    /**
     * @test
     * @dataProvider eventDataProvider
     *
     * @param CdbXmlDocument $originalCdbXmlDocument
     * @param OrganizerEvent|ActorEvent $event
     * @param CdbXmlDocument $expectedCdbXmlDocument
     */
    public function it_applies_a_category(
        CdbXmlDocument $originalCdbXmlDocument,
        $event,
        CdbXmlDocument $expectedCdbXmlDocument
    ) {
        $this->repository->save($originalCdbXmlDocument);

        if ($event instanceof OrganizerEvent) {
            $organizerId = $event->getOrganizerId();
        } elseif ($event instanceof ActorEvent) {
            $organizerId = $event->getActorId();
        } else {
            $this->fail('Could not determine organizer id from class ' . get_class($event));
            return;
        }

        $domainMessage = new DomainMessage(
            $organizerId,
            is_null($originalCdbXmlDocument) ? 0 : 1,
            new Metadata(),
            $event,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);

        $actualCdbXmlDocument = $this->repository->get($organizerId);

        $this->assertEquals($expectedCdbXmlDocument, $actualCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function eventDataProvider()
    {
        return [
            [
                new CdbXmlDocument(
                    'organizer',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/organizer.xml')
                ),
                new OrganizerCreated(
                    'organizer',
                    new Title('title'),
                    array(),
                    array(),
                    array(),
                    array()
                ),
                new CdbXmlDocument(
                    'organizer',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/organizer-with-category.xml')
                ),
            ],
            [
                new CdbXmlDocument(
                    'organizer',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/organizer.xml')
                ),
                new OrganizerImportedFromUDB2('organizer', 'cdbxml', 'cdbxml_namespace_uri'),
                new CdbXmlDocument(
                    'organizer',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/organizer-with-category.xml')
                ),
            ],
            [
                new CdbXmlDocument(
                    'organizer',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/organizer.xml')
                ),
                new OrganizerUpdatedFromUDB2('organizer', 'cdbxml', 'cdbxml_namespace_uri'),
                new CdbXmlDocument(
                    'organizer',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/organizer-with-category.xml')
                ),
            ],
        ];
    }
}
