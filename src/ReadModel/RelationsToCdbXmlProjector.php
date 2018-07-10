<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultureFeed_Cdb_Data_ContactInfo;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\Web\Url;

/**
 * Class RelationsToCdbXmlProjector
 * This projector takes CdbXml Events. It checks the relations and will update the cdbxml projections of the
 * dependencies/related items.
 */
class RelationsToCdbXmlProjector implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const CDBXML_NAMESPACE_URI = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var MetadataCdbItemEnricherInterface
     */
    private $metadataCdbItemEnricher;

    /**
     * @var DocumentRepositoryInterface
     */
    private $actorDocumentRepository;

    /**
     * @var OfferRelationsServiceInterface
     */
    private $offerRelationsService;

    /**
     * @var IriOfferIdentifierFactory
     */
    private $iriOfferIdentifierFactory;

    /**
     * RelationsToCdbXmlProjector constructor.
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param MetadataCdbItemEnricherInterface $metadataCdbItemEnricher
     * @param DocumentRepositoryInterface $actorDocumentRepository
     * @param OfferRelationsServiceInterface $offerRelationsService
     * @param IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        MetadataCdbItemEnricherInterface $metadataCdbItemEnricher,
        DocumentRepositoryInterface $actorDocumentRepository,
        OfferRelationsServiceInterface $offerRelationsService,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
    ) {
        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->metadataCdbItemEnricher = $metadataCdbItemEnricher;
        $this->actorDocumentRepository = $actorDocumentRepository;
        $this->offerRelationsService = $offerRelationsService;
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
        $this->logger = new NullLogger();
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $handlers = [
            OrganizerProjectedToCdbXml::class => 'applyOrganizerProjectedToCdbXml',
            PlaceProjectedToCdbXml::class => 'applyPlaceProjectedToCdbXml',
        ];

        if (isset($handlers[$payloadClassName])) {
            $this->logger->info(
                'handling message ' . $payloadClassName . ' using ' .
                $handlers[$payloadClassName] . ' in RelationsToCdbXmlProjector'
            );

            $handler = $handlers[$payloadClassName];
            $this->{$handler}($payload, $domainMessage);
        }
    }

    /**
     * @param OrganizerProjectedToCdbXml $organizerProjectedToCdbXml
     * @param DomainMessage $domainMessage
     */
    public function applyOrganizerProjectedToCdbXml(
        OrganizerProjectedToCdbXml $organizerProjectedToCdbXml,
        DomainMessage $domainMessage
    ) {
        $metadata = $domainMessage->getMetadata();

        $organizerId = $organizerProjectedToCdbXml->getOrganizerId();

        $eventIds = $this->offerRelationsService->getByOrganizer($organizerId);

        $organizer = $this->createOrganizer($organizerId);

        foreach ($eventIds as $eventId) {
            $event = $this->loadEventFromDocumentRepository($eventId);

            if (!$event) {
                continue;
            }

            $newEvent = clone $event;

            $newEvent->setOrganiser($organizer);

            $this->saveAndPublishIfChanged($newEvent, $event, $metadata);
        }
    }

    /**
     * @param PlaceProjectedToCdbXml $placeProjectedToCdbXml
     * @param DomainMessage $domainMessage
     */
    public function applyPlaceProjectedToCdbXml(
        PlaceProjectedToCdbXml $placeProjectedToCdbXml,
        DomainMessage $domainMessage
    ) {
        $metadata = $domainMessage->getMetadata();

        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative((string) $placeProjectedToCdbXml->getIri())
        );

        $placeId = $identifier->getId();

        $eventIds = $this->offerRelationsService->getByPlace(
            $placeId
        );

        $location = $this->createLocation($placeId);

        foreach ($eventIds as $eventId) {
            $event = $this->loadEventFromDocumentRepository($eventId);

            if (!$event) {
                continue;
            }

            $newEvent = clone $event;

            $newEvent->setLocation($location);

            $eventContactInfo = $event->getContactInfo();
            if (is_null($eventContactInfo)) {
                $eventContactInfo = new CultureFeed_Cdb_Data_ContactInfo();
            }

            foreach ($eventContactInfo->getAddresses() as $index => $address) {
                $eventContactInfo->removeAddress($index);
            }

            $eventContactInfo->addAddress($location->getAddress());

            $newEvent->setContactInfo($eventContactInfo);

            $this->saveAndPublishIfChanged($newEvent, $event, $metadata);
        }
    }

    /**
     * @param $placeId
     * @return \CultureFeed_Cdb_Data_Location
     */
    private function createLocation($placeId)
    {
        $placeCdbXml = $this->documentRepository->get($placeId);

        $place = $this->createActorFromCdbXml($placeCdbXml);

        $placeTitle = $place->getDetails()->getFirst()->getTitle();

        $addresses = !is_null($place->getContactInfo()) ? $place->getContactInfo()->getAddresses() : array();

        if (!empty($addresses)) {
            $address = $addresses[0];
            $location = new \CultureFeed_Cdb_Data_Location($address);
            $location->setCdbid($place->getCdbId());
            $location->setLabel($placeTitle);
            return $location;
        } else {
            return new \CultureFeed_Cdb_Data_Location(
                new \CultureFeed_Cdb_Data_Address(
                    null,
                    new \CultureFeed_Cdb_Data_Address_VirtualAddress($placeTitle)
                )
            );
        }
    }

    /**
     * @param $organizerId
     * @return \CultureFeed_Cdb_Data_Organiser
     */
    private function createOrganizer($organizerId)
    {
        // load organizer from documentRepo & add to document
        $organizerCdbXml = $this->actorDocumentRepository->get($organizerId);

        $actor = $this->createActorFromCdbXml($organizerCdbXml);

        $organizer = new \CultureFeed_Cdb_Data_Organiser();
        $organizer->setCdbid($organizerId);
        $organizer->setLabel($actor->getDetails()->getFirst()->getTitle());
        $organizer->setActor($actor);

        return $organizer;
    }

    /**
     * @param CultureFeed_Cdb_Item_Event $newEvent
     * @param CultureFeed_Cdb_Item_Event $event
     * @param Metadata $metadata
     */
    private function saveAndPublishIfChanged(
        CultureFeed_Cdb_Item_Event $newEvent,
        CultureFeed_Cdb_Item_Event $event,
        Metadata $metadata
    ) {
        if ($newEvent != $event) {
            $newEvent = $this->metadataCdbItemEnricher->enrichTime(
                $newEvent,
                $metadata
            );

            $newCdbXmlDocument = $this->cdbXmlDocumentFactory
                ->fromCulturefeedCdbItem($newEvent);

            $this->documentRepository->save($newCdbXmlDocument);
        }
    }

    /**
     * @param CdbXmlDocument $eventCdbXml
     * @return \CultureFeed_Cdb_Item_Event
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function createEventFromCdbXml(CdbXmlDocument $eventCdbXml): \CultureFeed_Cdb_Item_Event
    {
        $event = EventItemFactory::createEventFromCdbXml(
            self::CDBXML_NAMESPACE_URI,
            $eventCdbXml->getCdbXml()
        );
        return $event;
    }

    /**
     * @param CdbXmlDocument $actorCdbXml
     * @return \CultureFeed_Cdb_Item_Actor
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function createActorFromCdbXml(CdbXmlDocument $actorCdbXml): \CultureFeed_Cdb_Item_Actor
    {
        $actor = ActorItemFactory::createActorFromCdbXml(
            self::CDBXML_NAMESPACE_URI,
            $actorCdbXml->getCdbXml()
        );
        return $actor;
    }

    /**
     * @param string $eventId
     * @return \CultureFeed_Cdb_Item_Event|null
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function loadEventFromDocumentRepository(string $eventId): ?CultureFeed_Cdb_Item_Event
    {
        $eventCdbXml = $this->documentRepository->get($eventId);

        if (!$eventCdbXml) {
            $this->logger->alert(
                'Unable to load cdbxml of event with id {event_id}',
                [
                    'event_id' => $eventId,
                ]
            );

            return null;
        }

        $event = $this->createEventFromCdbXml($eventCdbXml);

        return $event;
    }
}
