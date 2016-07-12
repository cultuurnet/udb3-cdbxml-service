<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use ValueObjects\Web\Url;

class FlandersRegionRelationsCdbXmlProjectorTest extends PHPUnit_Framework_TestCase
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
     * @var IriOfferIdentifierFactoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $iriOfferIdentifierFactory;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var OfferRelationsServiceInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $offerRelationsService;

    /**
     * @var FlandersRegionRelationsCdbXmlProjector
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
        $event = $this->dataProvider()[0][2];
        $actualCdbXmlDocuments = $this->projector->applyFlandersRegionPlaceProjectedToCdbXml($event);
        $this->assertEquals(2, count($actualCdbXmlDocuments));

        $actualCdbXmlDocument = $actualCdbXmlDocuments[0];
        $actualCdbXml = $actualCdbXmlDocument->getCdbXml();
        $expectedCdbXml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-with-category.xml');
        $this->assertEquals($expectedCdbXml, $actualCdbXml);

        $actualCdbXmlDocument = $actualCdbXmlDocuments[1];
        $actualCdbXml = $actualCdbXmlDocument->getCdbXml();
        $expectedCdbXml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-with-publication-date-and-category.xml');
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
        $domainMessage = new DomainMessage('baz', 1, new Metadata(), $event, DateTime::now());
        $message = 'handling message ' . $class . ' using ' . $method . ' in FlandersRegionCdbXmlProjector';
        $this->logger->expects($this->at(1))->method('info')->with($message);
        $this->projector->handle($domainMessage);
    }

    public function dataProvider()
    {
        return array(
            array(
                PlaceProjectedToCdbXml::class,
                'applyFlandersRegionPlaceProjectedToCdbXml',
                new PlaceProjectedToCdbXml(
                    'http://foo.bar/place/baz'
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
        $this->offerRelationsService = $this->getMock(OfferRelationsServiceInterface::class);
        $this->iriOfferIdentifierFactory = $this->getMock(IriOfferIdentifierFactoryInterface::class);

        $this->projector = new FlandersRegionRelationsCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            $this->categories,
            $this->offerRelationsService,
            $this->iriOfferIdentifierFactory
        );

        $this->logger = $this->getMock(LoggerInterface::class);
        $this->projector->setLogger($this->logger);

        $eventId1 = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $eventId2 = '404EE8DE-E828-9C07-FE7D12DC4EB24481';

        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event.xml');
        $cdbXmlDocument = new CdbXmlDocument($eventId1, $xml);
        $this->repository->save($cdbXmlDocument);

        $xml = file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-with-publication-date.xml');
        $cdbXmlDocument = new CdbXmlDocument($eventId2, $xml);
        $this->repository->save($cdbXmlDocument);

        $placeId = 'baz';
        $placeIri = Url::fromNative('http://foo.bar/place/' . $placeId);
        $placeIdentifier = new IriOfferIdentifier($placeIri, $placeId, OfferType::PLACE());

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($placeIri)
            ->willReturn($placeIdentifier);

        $this->offerRelationsService
            ->expects($this->once())
            ->method('getByPlace')
            ->willReturn(
                array($eventId1, $eventId2)
            );
    }
}
