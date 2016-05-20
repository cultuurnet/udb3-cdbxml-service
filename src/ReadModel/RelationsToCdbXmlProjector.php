<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\NullCdbXmlPublisher;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use ValueObjects\Number\Natural;
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
     * @return OfferToEventCdbXmlProjector
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
        $metadata = $domainMessage->getMetadata();

        $organizerId = $organizerProjectedToCdbXml->getOrganizerId();

        $eventIds = $this->offerRelationsService->getByOrganizer($organizerId);

        foreach ($eventIds as $eventId) {
            $eventCdbXml = $this->documentRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

            // load organizer from documentRepo & add to document
            $organizerCdbXml = $this->actorDocumentRepository->get($organizerProjectedToCdbXml->getOrganizerId());

            $actor = ActorItemFactory::createActorFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $organizerCdbXml->getCdbXml()
            );

            $newEvent = clone $event;

            $organizer = new \CultureFeed_Cdb_Data_Organiser();
            $organizer->setCdbid($organizerProjectedToCdbXml->getOrganizerId());
            $organizer->setLabel($actor->getDetails()->getDetailByLanguage('nl')->getTitle());
            $organizer->setActor($actor);

            $newEvent->setOrganiser($organizer);

            if ($newEvent != $event) {
                // Change the lastupdated attribute.
                $metadataArray = $metadata->serialize();

                if (isset($metadataArray['request_time'])) {
                    $newEvent = $this->metadataCdbItemEnricher
                        ->enrichTime($newEvent, new Natural((int) $metadataArray['request_time']));
                }

                $newCdbXmlDocument = $this->cdbXmlDocumentFactory
                    ->fromCulturefeedCdbItem($newEvent);

                $this->documentRepository->save($newCdbXmlDocument);

                $this->cdbXmlPublisher->publish($newCdbXmlDocument, $domainMessage);
            }
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

        foreach ($eventIds as $eventId) {
            $eventCdbXml = $this->documentRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

            $newEvent = clone $event;

            $this->setLocation($placeId, $newEvent);

            if ($newEvent != $event) {
                // Change the lastupdated attribute.
                $metadataArray = $metadata->serialize();

                if (isset($metadataArray['request_time'])) {
                    $newEvent = $this->metadataCdbItemEnricher
                        ->enrichTime($newEvent, new Natural((int) $metadataArray['request_time']));
                }

                $newCdbXmlDocument = $this->cdbXmlDocumentFactory
                    ->fromCulturefeedCdbItem($newEvent);

                $this->documentRepository->save($newCdbXmlDocument);

                $this->cdbXmlPublisher->publish($newCdbXmlDocument, $domainMessage);
            }
        }
    }

    /**
     * Set the location on the cdb event based on an eventCreated event.
     *
     * @param string $placeId
     * @param CultureFeed_Cdb_Item_Event $cdbEvent
     * @throws \CultuurNet\UDB3\EntityNotFoundException
     */
    private function setLocation($placeId, CultureFeed_Cdb_Item_Event $cdbEvent)
    {
        $placeCdbXml = $this->documentRepository->get($placeId);

        $place = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXml->getCdbXml()
        );

        $location = $place->getLocation();

        $cdbEvent->setLocation($location);
    }
}
