<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\CdbXmlService\Relations\Event\RepositoryInterface as EventRelationsRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\Relations\Place\RepositoryInterface as PlaceRelationsRepositoryInterface;

class OfferRelationsServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OfferRelationsServiceInterface
     */
    private $offerRelationsService;

    /**
     * @var EventRelationsRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventRelationsRepository;

    /**
     * @var PlaceRelationsRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $placeRelationsRepository;

    /**
     * setup
     */
    public function setUp()
    {
        $this->eventRelationsRepository = $this->createMock(EventRelationsRepositoryInterface::class);
        $this->placeRelationsRepository = $this->createMock(PlaceRelationsRepositoryInterface::class);

        $this->offerRelationsService = new OfferRelationsService(
            $this->eventRelationsRepository,
            $this->placeRelationsRepository
        );
    }

    /**
     * @test
     */
    public function it_returns_eventids_and_placeids_when_getting_by_organizer()
    {
        $organizerId = 'ORG-id-123';

        $eventIds = [
            'EvEnT-ID-1',
            'EvEnT-ID-2',
            'EvEnT-ID-3',
        ];

        $placeIds = [
            'PlAcE-ID-1',
            'PlAcE-ID-2',
            'PlAcE-ID-3',
        ];

        $expectedIds = [
            'EvEnT-ID-1',
            'EvEnT-ID-2',
            'EvEnT-ID-3',
            'PlAcE-ID-1',
            'PlAcE-ID-2',
            'PlAcE-ID-3',
        ];

        $this->eventRelationsRepository->expects($this->once())
            ->method('getEventsOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn($eventIds);

        $this->placeRelationsRepository->expects($this->once())
            ->method('getPlacesOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn($placeIds);

        $ids = $this->offerRelationsService->getByOrganizer($organizerId);

        $this->assertEquals($expectedIds, $ids);
    }

    /**
     * @test
     */
    public function it_returns_event_ids_when_getting_by_place()
    {
        $placeId = '34973B89-BDA3-4A79-96C7-78ACC022907D';

        $eventIds = [
            'EvEnT-ID-1',
            'EvEnT-ID-2',
            'EvEnT-ID-3',
        ];

        $expectedIds = [
            'EvEnT-ID-1',
            'EvEnT-ID-2',
            'EvEnT-ID-3',
        ];

        $this->eventRelationsRepository->expects($this->once())
            ->method('getEventsLocatedAtPlace')
            ->with($placeId)
            ->willReturn($eventIds);

        $ids = $this->offerRelationsService->getByPlace($placeId);

        $this->assertEquals($expectedIds, $ids);
    }
}
