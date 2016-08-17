<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultureFeed_Cdb_Data_ContactInfo;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\NullCdbXmlPublisher;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use ValueObjects\Web\Url;

/**
 * Class RelationsToCdbXmlProjector
 * This projector takes CdbXml Events. It checks the relations and will update the cdbxml projections of the
 * dependencies/related items.
 */
class RelationsToCdbXmlProjector implements EventListenerInterface
{
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
     * @var CdbXmlPublisherInterface
     */
    private $cdbXmlPublisher;

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
        $this->cdbXmlPublisher = new NullCdbXmlPublisher();
        $this->actorDocumentRepository = $actorDocumentRepository;
        $this->offerRelationsService = $offerRelationsService;
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    /**
     * @param CdbXmlPublisherInterface $cdbXmlPublisher
     * @return RelationsToCdbXmlProjector
     */
    public function withCdbXmlPublisher(CdbXmlPublisherInterface $cdbXmlPublisher)
    {
        $c = clone $this;
        $c->cdbXmlPublisher = $cdbXmlPublisher;
        return $c;
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
        $organizerId = $organizerProjectedToCdbXml->getOrganizerId();

        $eventIds = $this->offerRelationsService->getByOrganizer($organizerId);

        $organizer = $this->createOrganizer($organizerId);

        foreach ($eventIds as $eventId) {
            $eventCdbXml = $this->documentRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

            $newEvent = clone $event;

            $newEvent->setOrganiser($organizer);

            $domainMessage = $this->fixDomainMessage(
                $domainMessage,
                $newEvent,
                $organizer->getActor()->getLastUpdated()
            );
            $this->saveAndPublishIfChanged($newEvent, $event, $domainMessage);
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
        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative((string) $placeProjectedToCdbXml->getIri())
        );

        $placeId = $identifier->getId();

        $eventIds = $this->offerRelationsService->getByPlace(
            $placeId
        );

        $location = $this->createLocation($placeId);

        foreach ($eventIds as $eventId) {
            $eventCdbXml = $this->documentRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

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

            $domainMessage = $this->fixDomainMessage(
                $domainMessage,
                $newEvent,
                $location->getActor()->getLastUpdated()
            );
            $this->saveAndPublishIfChanged($newEvent, $event, $domainMessage);
        }
    }

    /**
     * @param $placeId
     * @return \CultureFeed_Cdb_Data_Location
     */
    private function createLocation($placeId)
    {
        $placeCdbXml = $this->documentRepository->get($placeId);

        $place = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXml->getCdbXml()
        );

        $placeTitle = $place->getDetails()->getDetailByLanguage('nl')->getTitle();

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

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $organizerCdbXml->getCdbXml()
        );

        $organizer = new \CultureFeed_Cdb_Data_Organiser();
        $organizer->setCdbid($organizerId);
        $organizer->setLabel($actor->getDetails()->getDetailByLanguage('nl')->getTitle());
        $organizer->setActor($actor);

        return $organizer;
    }

    /**
     * @param CultureFeed_Cdb_Item_Event $newEvent
     * @param CultureFeed_Cdb_Item_Event $event
     * @param DomainMessage $domainMessage
     */
    private function saveAndPublishIfChanged(
        CultureFeed_Cdb_Item_Event $newEvent,
        CultureFeed_Cdb_Item_Event $event,
        DomainMessage $domainMessage
    ) {
        if ($newEvent != $event) {
            $newEvent = $this->metadataCdbItemEnricher->enrichTime(
                $newEvent,
                $domainMessage->getMetadata()
            );

            $newCdbXmlDocument = $this->cdbXmlDocumentFactory
                ->fromCulturefeedCdbItem($newEvent);

            $this->documentRepository->save($newCdbXmlDocument);

            $this->cdbXmlPublisher->publish($newCdbXmlDocument, $domainMessage);
        }
    }

    /**
     * @param DomainMessage $domainMessage
     * @param CultureFeed_Cdb_Item_Event $cdbItemEvent
     * @param string $lastUpdated
     * @return DomainMessage
     */
    private function fixDomainMessage(
        DomainMessage $domainMessage,
        CultureFeed_Cdb_Item_Event $cdbItemEvent,
        $lastUpdated
    ) {
        $metadata = new Metadata(
            [
                'id' => $cdbItemEvent->getExternalId(),
                'request_time' => $lastUpdated
            ]
        );

        return $domainMessage->andMetadata($metadata);
    }
}
