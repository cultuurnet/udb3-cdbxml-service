<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface as EventRelationsRepositoryInterface;
use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface as PlaceRelationsRepositoryInterface;

class OfferRelationsService implements OfferRelationsServiceInterface
{

    /**
     * @var EventRelationsRepositoryInterface
     */
    protected $eventRelationsRepository;

    /**
     * @var PlaceRelationsRepositoryInterface
     */
    protected $placeRelationsRepository;

    public function __construct(
        EventRelationsRepositoryInterface $eventRelationsRepository,
        PlaceRelationsRepositoryInterface $placeRelationsRepository
    ) {
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->placeRelationsRepository = $placeRelationsRepository;
    }

    /**
     * @param string $organizerId
     * @return string[]
     */
    public function getByOrganizer($organizerId)
    {
        // Get the event ids.
        $eventIds = $this->eventRelationsRepository->getEventsOrganizedByOrganizer($organizerId);

        // Get the place ids.
        $placeIds = $this->placeRelationsRepository->getPlacesOrganizedByOrganizer($organizerId);

        // Now merge the ids to return one list.
        $eventIds = array_merge($eventIds, $placeIds);

        return $eventIds;
    }

    /**
     * @param string $placeId
     * @return string[]
     */
    public function getByPlace($placeId)
    {
        $eventIds = $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);

        return $eventIds;
    }
}
