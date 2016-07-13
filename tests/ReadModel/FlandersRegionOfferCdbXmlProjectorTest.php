<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

class FlandersRegionOfferCdbXmlProjectorTest extends PHPUnit_Framework_TestCase
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
     * @var FlandersRegionOfferCdbXmlProjector
     */
    private $projector;

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @test
     */
    public function it_applies_a_category_on_events()
    {
        /* @var EventCreated $event */
        $event = $this->dataProvider()[0][2];

        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event.xml');
        $cdbXmlDocument = new CdbXmlDocument($event->getEventId(), $xml);
        $this->repository->save($cdbXmlDocument);

        $actualCdbXmlDocuments = $this->projector->applyFlandersRegionEventAddedUpdated($event);
        $this->assertEquals(1, count($actualCdbXmlDocuments));

        $actualCdbXmlDocument = $actualCdbXmlDocuments[0];
        $actualCdbXml = $actualCdbXmlDocument->getCdbXml();

        $expectedCdbXml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-with-category.xml');

        $this->assertEquals($expectedCdbXml, $actualCdbXml);
    }

    /**
     * @test
     */
    public function it_applies_a_category_on_places()
    {
        /* @var PlaceCreated $event */
        $event = $this->dataProvider()[2][2];

        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/place.xml');
        $cdbXmlDocument = new CdbXmlDocument($event->getPlaceId(), $xml);
        $this->repository->save($cdbXmlDocument);

        $actualCdbXmlDocuments = $this->projector->applyFlandersRegionPlaceAddedUpdated($event);
        $this->assertEquals(1, count($actualCdbXmlDocuments));

        $actualCdbXmlDocument = $actualCdbXmlDocuments[0];
        $actualCdbXml = $actualCdbXmlDocument->getCdbXml();

        $expectedCdbXml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/place-with-category.xml');

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
                EventCreated::class,
                'applyFlandersRegionEventAddedUpdated',
                new EventCreated(
                    'event_created',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Location('', '', '', '', '', ''),
                    new Calendar(Calendar::PERMANENT)
                ),
            ),
            array(
                EventMajorInfoUpdated::class,
                'applyFlandersRegionEventAddedUpdated',
                new EventMajorInfoUpdated(
                    'event_updated',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Location('', '', '', '', '', ''),
                    new Calendar(Calendar::PERMANENT)
                ),
            ),
            array(
                PlaceCreated::class,
                'applyFlandersRegionPlaceAddedUpdated',
                new PlaceCreated(
                    'place_created',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Address('', '', '', ''),
                    new Calendar(Calendar::PERMANENT)
                ),
            ),
            array(
                PlaceMajorInfoUpdated::class,
                'applyFlandersRegionPlaceAddedUpdated',
                new PlaceMajorInfoUpdated(
                    'place_updated',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Address('', '', '', ''),
                    new Calendar(Calendar::PERMANENT)
                ),
            ),
        );
    }

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->repository = new CacheDocumentRepository($this->cache);
        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/term.xml');
        $this->categories = new FlandersRegionCategories($xml);

        $this->projector = new FlandersRegionOfferCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            $this->categories
        );

        $this->logger = $this->getMock(LoggerInterface::class);
        $this->projector->setLogger($this->logger);

        $this->organizerId = 'ORG-123';
    }
}
