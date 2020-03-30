<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated as EventGeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated as PlaceGeoCoordinatesUpdated;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeocodingOfferCdbXmlProjectorTest extends TestCase
{
    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $cdbXmlDocumentRepository;

    /**
     * @var CdbXmlDocumentFactory
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var OfferRelationsServiceInterface|MockObject
     */
    private $offerRelationsService;

    /**
     * @var GeocodingOfferCdbXmlProjector
     */
    private $projector;

    protected function setUp(): void
    {
        $this->cdbXmlDocumentRepository = new CacheDocumentRepository(new ArrayCache());
        $this->cdbXmlDocumentFactory = new CdbXmlDocumentFactory('3.3');
        $this->offerRelationsService = $this->createMock(OfferRelationsServiceInterface::class);

        $this->projector = new GeocodingOfferCdbXmlProjector(
            $this->cdbXmlDocumentRepository,
            $this->cdbXmlDocumentFactory,
            $this->offerRelationsService
        );
    }

    /**
     * @test
     */
    public function it_geocodes_events()
    {
        $originalEvent = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Repository/samples/event.xml')
        );
        $this->cdbXmlDocumentRepository->save($originalEvent);

        $coordinates = new Coordinates(
            new Latitude(50.9692424),
            new Longitude(4.6910644)
        );
        $geoCoordinatesUpdated = new EventGeoCoordinatesUpdated(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            $coordinates
        );
        $domainMessage = new DomainMessage(
            $geoCoordinatesUpdated->getItemId(),
            1,
            new Metadata(),
            $geoCoordinatesUpdated,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);
        $actualEvent = $this->cdbXmlDocumentRepository->get($geoCoordinatesUpdated->getItemId());

        $expectedEvent = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Repository/samples/event-with-geocoordinates.xml')
        );
        $this->assertEquals($expectedEvent, $actualEvent);
    }

    /**
     * @test
     */
    public function it_geocodes_places_and_related_events()
    {
        $originalPlace = new CdbXmlDocument(
            '34973B89-BDA3-4A79-96C7-78ACC022907D',
            file_get_contents(__DIR__ . '/Repository/samples/place.xml')
        );
        $this->cdbXmlDocumentRepository->save($originalPlace);

        $originalEvents = [
            new CdbXmlDocument(
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                file_get_contents(__DIR__ . '/Repository/samples/event.xml')
            ),
        ];
        foreach ($originalEvents as $originalEvent) {
            $this->cdbXmlDocumentRepository->save($originalEvent);
        }

        $coordinates = new Coordinates(
            new Latitude(50.9692424),
            new Longitude(4.6910644)
        );
        $geoCoordinatesUpdated = new PlaceGeoCoordinatesUpdated(
            '34973B89-BDA3-4A79-96C7-78ACC022907D',
            $coordinates
        );
        $domainMessage = new DomainMessage(
            $geoCoordinatesUpdated->getItemId(),
            1,
            new Metadata(),
            $geoCoordinatesUpdated,
            DateTime::now()
        );

        $relatedEventIds = ['404EE8DE-E828-9C07-FE7D12DC4EB24480'];

        $this->offerRelationsService->expects($this->once())
            ->method('getByPlace')
            ->with($geoCoordinatesUpdated->getItemId())
            ->willReturn($relatedEventIds);

        $this->projector->handle($domainMessage);

        $actualPlace = $this->cdbXmlDocumentRepository->get($geoCoordinatesUpdated->getItemId());

        $expectedPlace = new CdbXmlDocument(
            '34973B89-BDA3-4A79-96C7-78ACC022907D',
            file_get_contents(__DIR__ . '/Repository/samples/place-with-geocoordinates.xml')
        );
        $this->assertEquals($expectedPlace, $actualPlace);

        $expectedEvents = [
            new CdbXmlDocument(
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                file_get_contents(__DIR__ . '/Repository/samples/event-with-geocoordinates.xml')
            ),
        ];

        foreach ($expectedEvents as $expectedEvent) {
            $actualEvent = $this->cdbXmlDocumentRepository->get($expectedEvent->getId());

            $this->assertEquals($expectedEvent, $actualEvent);
        }
    }
}
