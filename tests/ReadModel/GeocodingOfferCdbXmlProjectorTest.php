<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\Geocoding\GeocodingServiceInterface;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Location;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;

class GeocodingOfferCdbXmlProjectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cdbXmlDocumentRepository;

    /**
     * @var CdbXmlDocumentFactory
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var OfferRelationsServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $offerRelationsService;

    /**
     * @var DefaultAddressFormatter
     */
    private $addressFormatter;

    /**
     * @var GeocodingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $geocodingService;

    /**
     * @var GeocodingOfferCdbXmlProjector
     */
    private $projector;

    public function setUp()
    {
        $this->cdbXmlDocumentRepository = new CacheDocumentRepository(new ArrayCache());
        $this->cdbXmlDocumentFactory = new CdbXmlDocumentFactory('3.3');
        $this->offerRelationsService = $this->getMock(OfferRelationsServiceInterface::class);
        $this->addressFormatter = new DefaultAddressFormatter();
        $this->geocodingService = $this->getMock(GeocodingServiceInterface::class);

        $this->projector = new GeocodingOfferCdbXmlProjector(
            $this->cdbXmlDocumentRepository,
            $this->cdbXmlDocumentFactory,
            $this->offerRelationsService,
            $this->addressFormatter,
            $this->geocodingService
        );
    }

    /**
     * @test
     * @dataProvider eventDataProvider
     * @param CdbXmlDocument $originalCdbXmlDocument
     * @param EventCreated|EventMajorInfoUpdated $event
     * @param string $address
     * @param Coordinates $coordinates
     * @param CdbXmlDocument $expectedCdbXmlDocument
     */
    public function it_geocodes_events(
        CdbXmlDocument $originalCdbXmlDocument,
        $event,
        $address,
        Coordinates $coordinates,
        CdbXmlDocument $expectedCdbXmlDocument
    ) {
        $this->cdbXmlDocumentRepository->save($originalCdbXmlDocument);

        $this->geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with($address)
            ->willReturn($coordinates);

        $domainMessage = new DomainMessage(
            $event->getEventId(),
            1,
            new Metadata(),
            $event,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);

        $actualCdbXmlDocument = $this->cdbXmlDocumentRepository->get($event->getEventId());

        $this->assertEquals($expectedCdbXmlDocument, $actualCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function eventDataProvider()
    {
        $originalCdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Repository/samples/event.xml')
        );

        $address = '$street, $postalCode $locality, $country';

        $coordinates = new Coordinates(
            new Latitude(50.9692424),
            new Longitude(4.6910644)
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            '404EE8DE-E828-9C07-FE7D12DC4EB24480',
            file_get_contents(__DIR__ . '/Repository/samples/event-with-geocoordinates.xml')
        );

        return [
            [
                $originalCdbXmlDocument,
                new EventCreated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Location('', '', '$country', '$locality', '$postalCode', '$street'),
                    new Calendar(Calendar::PERMANENT)
                ),
                $address,
                $coordinates,
                $expectedCdbXmlDocument,
            ],
            [
                $originalCdbXmlDocument,
                new EventMajorInfoUpdated(
                    '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Location('', '', '$country', '$locality', '$postalCode', '$street'),
                    new Calendar(Calendar::PERMANENT)
                ),
                $address,
                $coordinates,
                $expectedCdbXmlDocument,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider placeDataProvider
     * @param CdbXmlDocument $originalCdbXmlDocument
     * @param PlaceCreated|PlaceMajorInfoUpdated $event
     * @param string $address
     * @param Coordinates $coordinates
     * @param CdbXmlDocument $expectedCdbXmlDocument
     * @param array $relatedEventIds
     * @param CdbXmlDocument[] $originalEventCdbXmlDocuments
     * @param CdbXmlDocument[] $expectedEventCdbXmlDocuments
     */
    public function it_geocodes_places_and_related_events(
        CdbXmlDocument $originalCdbXmlDocument,
        $event,
        $address,
        Coordinates $coordinates,
        CdbXmlDocument $expectedCdbXmlDocument,
        array $relatedEventIds,
        array $originalEventCdbXmlDocuments,
        array $expectedEventCdbXmlDocuments
    ) {
        $this->cdbXmlDocumentRepository->save($originalCdbXmlDocument);

        foreach ($originalEventCdbXmlDocuments as $originalEventCdbXmlDocument) {
            $this->cdbXmlDocumentRepository->save($originalEventCdbXmlDocument);
        }

        $this->offerRelationsService->expects($this->once())
            ->method('getByPlace')
            ->with($event->getPlaceId())
            ->willReturn($relatedEventIds);

        $this->geocodingService->expects($this->exactly(1 + count($relatedEventIds)))
            ->method('getCoordinates')
            ->with($address)
            ->willReturn($coordinates);

        $domainMessage = new DomainMessage(
            $event->getPlaceId(),
            1,
            new Metadata(),
            $event,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);

        $actualCdbXmlDocument = $this->cdbXmlDocumentRepository->get($event->getPlaceId());

        $this->assertEquals($expectedCdbXmlDocument, $actualCdbXmlDocument);

        foreach ($expectedEventCdbXmlDocuments as $expectedEventCdbXmlDocument) {
            $actualEventCdbXmlDocument = $this->cdbXmlDocumentRepository->get(
                $expectedEventCdbXmlDocument->getId()
            );

            $this->assertEquals($expectedEventCdbXmlDocument, $actualEventCdbXmlDocument);
        }
    }

    /**
     * @return array
     */
    public function placeDataProvider()
    {
        $originalCdbXmlDocument = new CdbXmlDocument(
            '34973B89-BDA3-4A79-96C7-78ACC022907D',
            file_get_contents(__DIR__ . '/Repository/samples/place.xml')
        );

        $address = '$street, $postalCode $locality, $country';

        $coordinates = new Coordinates(
            new Latitude(50.9692424),
            new Longitude(4.6910644)
        );

        $expectedCdbXmlDocument = new CdbXmlDocument(
            '34973B89-BDA3-4A79-96C7-78ACC022907D',
            file_get_contents(__DIR__ . '/Repository/samples/place-with-geocoordinates.xml')
        );

        $relatedEventIds = ['404EE8DE-E828-9C07-FE7D12DC4EB24480'];

        $originalEventCdbXmlDocuments = [
            new CdbXmlDocument(
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                file_get_contents(__DIR__ . '/Repository/samples/event.xml')
            ),
        ];

        $expectedEventCdbXmlDocuments = [
            new CdbXmlDocument(
                '404EE8DE-E828-9C07-FE7D12DC4EB24480',
                file_get_contents(__DIR__ . '/Repository/samples/event-with-geocoordinates.xml')
            ),
        ];

        return [
            [
                $originalCdbXmlDocument,
                new PlaceCreated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Address('$street', '$postalCode', '$locality', '$country'),
                    new Calendar(Calendar::PERMANENT)
                ),
                $address,
                $coordinates,
                $expectedCdbXmlDocument,
                $relatedEventIds,
                $originalEventCdbXmlDocuments,
                $expectedEventCdbXmlDocuments,
            ],
            [
                $originalCdbXmlDocument,
                new PlaceMajorInfoUpdated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Title('title'),
                    new EventType('id', 'label'),
                    new Address('$street', '$postalCode', '$locality', '$country'),
                    new Calendar(Calendar::PERMANENT)
                ),
                $address,
                $coordinates,
                $expectedCdbXmlDocument,
                $relatedEventIds,
                $originalEventCdbXmlDocuments,
                $expectedEventCdbXmlDocuments,
            ],
        ];
    }
}
