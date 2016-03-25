<?php

namespace CultuurNet\UDB3\CDBXMLService;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated as EventOrganizerUpdated;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated as PlaceOrganizerUpdated;

/**
 * Class EventEnricher
 *  Enrich UDB3 domain events with additional data required to project to CDBXML.
 * @package CultuurNet\UDB3\CDBXMLService
 */
class EventEnricher implements EventListenerInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $organizerRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $offerRepository;

    private static $eventHandlers = [
        EventOrganizerUpdated::class => 'enrichOrganizerUpdated',
        PlaceOrganizerUpdated::class => 'enrichOrganizerUpdated',
        EventCreated::class => 'enrichEventCreated',
        MajorInfoUpdated::class => 'enrichMajorInfoUpdated'
    ];

    /**
     * EventEnricher constructor.
     */
    public function __construct(
        DocumentRepositoryInterface $organizerRepository,
        DocumentRepositoryInterface $offerRepository
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->offerRepository = $offerRepository;
    }


    public function handle(DomainMessage $domainMessage)
    {
        $className = get_class($domainMessage->getPayload());

        if (isset(self::$eventHandlers[$className])) {
            $handler = self::$eventHandlers[$className];
            $enrichedPayload = $this->{$handler}($domainMessage);
            $enrichedMessage = new DomainMessage(
                // $_$
            );
        }
    }

    private function enrichOrganizerUpdated(OrganizerUpdated $organizerUpdated) {
        $organizerDocument = $this->organizerRepository->get(
            $organizerUpdated->getOrganizerId()
        );

        $organizer = \CultureFeed_Cdb_Item_Actor::parseFromCdbXml(
            new \SimpleXMLElement($organizerDocument->getCDBXML())
        );

        /** @var \CultureFeed_Cdb_Data_Detail[] $details */
        $details = $organizer->getDetails();
        $detail = null;

        foreach ($details as $languageDetail) {
            // The first language detail found will be used to retrieve
            // properties from which in UDB3 are not any longer considered
            // to be language specific.
            if (!$detail) {
                $detail = $languageDetail;
            }
        }

        $name = isset($detail) ? $detail->getTitle() : '';

        return new EnrichedOrganizerUpdated($organizerUpdated->getItemId(), $name);
    }
    
}
