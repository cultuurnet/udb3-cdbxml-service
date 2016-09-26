<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactory;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\FlandersRegionCategoryService;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CacheDocumentRepository;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Location\Location;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\PlaceEvent;
use CultuurNet\UDB3\Title;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\String\String as StringLiteral;

class FlandersRegionOfferCdbXmlProjectorTest extends PHPUnit_Framework_TestCase
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
     * @var FlandersRegionOfferCdbXmlProjector
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

        $this->projector = new FlandersRegionOfferCdbXmlProjector(
            $this->repository,
            new CdbXmlDocumentFactory('3.3'),
            $this->categories
        );

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->logger->expects($this->never())
            ->method('error');

        $this->projector->setLogger($this->logger);
    }

    /**
     * @test
     * @dataProvider eventDataProvider
     *
     * @param CdbXmlDocument $originalCdbXmlDocument
     * @param EventEvent|PlaceEvent $event
     * @param CdbXmlDocument $expectedCdbXmlDocument
     */
    public function it_applies_a_category_on_events(
        CdbXmlDocument $originalCdbXmlDocument,
        $event,
        CdbXmlDocument $expectedCdbXmlDocument
    ) {
        $this->repository->save($originalCdbXmlDocument);

        if ($event instanceof EventEvent) {
            $offerId = $event->getEventId();
        } elseif ($event instanceof PlaceEvent) {
            $offerId = $event->getPlaceId();
        } elseif ($event instanceof AbstractEvent) {
            $offerId = $event->getItemId();
        } else {
            $this->fail('Could not determine offer id from given event.');
            return;
        }

        $domainMessage = new DomainMessage(
            $offerId,
            is_null($originalCdbXmlDocument) ? 0 : 1,
            new Metadata(),
            $event,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);

        $actualCdbXmlDocument = $this->repository->get($offerId);

        $this->assertEquals($expectedCdbXmlDocument, $actualCdbXmlDocument);
    }

    /**
     * @return array
     */
    public function eventDataProvider()
    {
        $address = new Address(
            new Street('Bondgenotenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $location = new Location(
            UUID::generateAsString(),
            new StringLiteral('Bibberburcht'),
            $address
        );

        return [
            [
                new CdbXmlDocument(
                    'event_1_id',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-1.xml')
                ),
                new EventCreated(
                    'event_1_id',
                    new Title('title'),
                    new EventType('id', 'label'),
                    $location,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new CdbXmlDocument(
                    'event_1_id',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-1-with-category.xml')
                ),
            ],
            [
                new CdbXmlDocument(
                    'event_1_id',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-1.xml')
                ),
                new EventMajorInfoUpdated(
                    'event_1_id',
                    new Title('title'),
                    new EventType('id', 'label'),
                    $location,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new CdbXmlDocument(
                    'event_1_id',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/event-1-with-category.xml')
                ),
            ],
            [
                new CdbXmlDocument(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/place.xml')
                ),
                new PlaceCreated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Title('title'),
                    new EventType('id', 'label'),
                    $address,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new CdbXmlDocument(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/place-with-category.xml')
                ),
            ],
            [
                new CdbXmlDocument(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/place.xml')
                ),
                new PlaceMajorInfoUpdated(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    new Title('title'),
                    new EventType('id', 'label'),
                    $address,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new CdbXmlDocument(
                    '34973B89-BDA3-4A79-96C7-78ACC022907D',
                    file_get_contents(__DIR__ . '/Repository/samples/flanders_region/place-with-category.xml')
                ),
            ],
        ];
    }
}
