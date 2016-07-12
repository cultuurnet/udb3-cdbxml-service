<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

class FlandersRegionActorCdbXmlProjectorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayCache
     */
    private $cache;

    /**
     * @var FlandersRegionCategories
     */
    private $categories;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var FlandersRegionActorCdbXmlProjector
     */
    private $projector;

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @test
     */
    public function it_applies_a_category()
    {
        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/actor-with-contact-info.xml');
        $cdbXmlDocument = new CdbXmlDocument($this->organizerId, $xml);
        $this->repository->save($cdbXmlDocument);

        $event = $this->dataProvider()[0][2];
        $actualCdbXmlDocuments = $this->projector->applyFlandersRegionOrganizerCreatedImportedUpdated($event);
        $this->assertEquals(1, count($actualCdbXmlDocuments));

        $actualCdbXmlDocument = $actualCdbXmlDocuments[0];
        $actualCdbXml = $actualCdbXmlDocument->getCdbXml();

        $expectedCdbXml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/actor-with-contact-info-and-category.xml');

        $this->assertEquals($expectedCdbXml, $actualCdbXml);
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @param string $class
     * @param string $method
     * @param mixed $event
     */
    public function it_returns_handlers($class, $method, $event)
    {
        $domainMessage = new DomainMessage($this->organizerId, 1, new Metadata(), $event, DateTime::now());
        $message = 'handling message ' . $class . ' using ' . $method . ' in FlandersRegionCdbXmlProjector';
        $this->logger->expects($this->at(1))->method('info')->with($message);
        $this->projector->handle($domainMessage);
    }

    public function dataProvider()
    {
        return array(
            array(
                OrganizerCreated::class,
                'applyFlandersRegionOrganizerCreatedImportedUpdated',
                new OrganizerCreated(
                    $this->organizerId,
                    new Title('foo'),
                    array(),
                    array(),
                    array(),
                    array()
                ),
            ),
            array(
                OrganizerImportedFromUDB2::class,
                'applyFlandersRegionOrganizerCreatedImportedUpdated',
                new OrganizerImportedFromUDB2($this->organizerId, 'foo', 'bar'),
            ),
            array(
                OrganizerUpdatedFromUDB2::class,
                'applyFlandersRegionOrganizerCreatedImportedUpdated',
                new OrganizerUpdatedFromUDB2($this->organizerId, 'foo', 'bar'),
            ),
        );
    }

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/term.xml');
        $this->categories = new FlandersRegionCategories($xml);

        $this->projector = new FlandersRegionActorCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            $this->categories
        );

        $this->logger = $this->getMock(LoggerInterface::class);
        $this->projector->setLogger($this->logger);

        $this->organizerId = 'ORG-123';
    }
}
