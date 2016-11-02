<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultureFeed_Cdb_Data_ContactInfo;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\Events\OrganizerProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\Labels\LabelApplierInterface;
use CultuurNet\UDB3\CdbXmlService\Labels\LabelFilterInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Organizer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
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
     * @var LabelFilterInterface
     */
    private $uitpasLabelFilter;

    /**
     * @var LabelApplierInterface
     */
    private $uitpasLabelApplier;

    /**
     * RelationsToCdbXmlProjector constructor.
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param MetadataCdbItemEnricherInterface $metadataCdbItemEnricher
     * @param DocumentRepositoryInterface $actorDocumentRepository
     * @param OfferRelationsServiceInterface $offerRelationsService
     * @param IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
     * @param LabelFilterInterface $uitpasLabelFilter
     * @param LabelApplierInterface $uitpasLabelApplier
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        MetadataCdbItemEnricherInterface $metadataCdbItemEnricher,
        DocumentRepositoryInterface $actorDocumentRepository,
        OfferRelationsServiceInterface $offerRelationsService,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory,
        LabelFilterInterface $uitpasLabelFilter,
        LabelApplierInterface $uitpasLabelApplier
    ) {
        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->metadataCdbItemEnricher = $metadataCdbItemEnricher;
        $this->actorDocumentRepository = $actorDocumentRepository;
        $this->offerRelationsService = $offerRelationsService;
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
        $this->uitpasLabelFilter = $uitpasLabelFilter;
        $this->uitpasLabelApplier = $uitpasLabelApplier;
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
            LabelAdded::class => 'applyOrganizerLabelAdded',
            LabelRemoved::class => 'applyOrganizerLabelRemoved',
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
            $eventCdbXml = $this->documentRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

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

            $this->saveAndPublishIfChanged($newEvent, $event, $metadata);
        }
    }

    /**
     * @param LabelAdded $labelAdded
     * @param DomainMessage $domainMessage
     */
    public function applyOrganizerLabelAdded(
        LabelAdded $labelAdded,
        DomainMessage $domainMessage
    ) {
        $this->applyLabelEvent($labelAdded, $domainMessage);
    }

    /**
     * @param LabelRemoved $labelRemoved
     * @param DomainMessage $domainMessage
     */
    public function applyOrganizerLabelRemoved(
        LabelRemoved $labelRemoved,
        DomainMessage $domainMessage
    ) {
        $this->applyLabelEvent($labelRemoved, $domainMessage);
    }

    /**
     * @param AbstractLabelEvent $labelEvent
     * @param DomainMessage $domainMessage
     */
    private function applyLabelEvent(
        AbstractLabelEvent $labelEvent,
        DomainMessage $domainMessage
    ) {
        $label = $this->getLabelName($domainMessage);

        // Only apply UiTPAS labels.
        $labelCollection = LabelCollection::fromStrings([$label]);
        if (count($this->uitpasLabelFilter->filter($labelCollection)) === 0) {
            return;
        }

        $eventIds = $this->offerRelationsService->getByOrganizer($labelEvent->getOrganizerId());

        foreach ($eventIds as $eventId) {
            $eventCdbXml = $this->documentRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

            $newEvent = clone $event;

            if ($labelEvent instanceof LabelAdded) {
                $newEvent = $this->uitpasLabelApplier->addLabels(
                    $newEvent,
                    $labelCollection
                );
            } else {
                $newEvent = $this->uitpasLabelApplier->removeLabels(
                    $newEvent,
                    $labelCollection
                );
            }

            $this->saveAndPublishIfChanged($newEvent, $event, $domainMessage->getMetadata());
        }
    }

    /**
     * @param DomainMessage $domainMessage
     * @return null|string
     */
    private function getLabelName(DomainMessage $domainMessage)
    {
        $metaDataAsArray = $domainMessage->getMetadata()->serialize();

        return isset($metaDataAsArray['labelName']) ?
            $metaDataAsArray['labelName'] : null;
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
}
