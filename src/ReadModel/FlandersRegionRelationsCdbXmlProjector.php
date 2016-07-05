<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\OfferRelationsServiceInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use ValueObjects\Web\Url;

/**
 * Class FlandersRegionRelationsCdbXmlProjector
 * This projector takes UDB3 domain messages, projects additional
 * flandersregion categories to CdbXml and then
 * publishes the changes to a public URL.
 */
class FlandersRegionRelationsCdbXmlProjector extends FlandersRegionAbstractCdbXmlProjector
{
    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $iriOfferIdentifierFactory;

    /**
     * @var OfferRelationsServiceInterface
     */
    private $offerRelationsService;

    /**
     * @var DocumentRepositoryInterface
     */
    private $realRepository;

    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        OfferRelationsServiceInterface $offerRelationsService,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
    ) {
        parent::__construct($documentRepository);

        $this->realRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->offerRelationsService = $offerRelationsService;
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlers()
    {
        return [
            PlaceProjectedToCdbXml::class => 'applyFlandersRegionPlaceProjectedToCdbXml',
        ];
    }

    /**
     * @param PlaceProjectedToCdbXml $placeProjectedToCdbXml
     *
     * @return CdbXmlDocument[]
     */
    public function applyFlandersRegionPlaceProjectedToCdbXml(
        PlaceProjectedToCdbXml $placeProjectedToCdbXml
    ) {
        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative((string) $placeProjectedToCdbXml->getIri())
        );

        $placeId = $identifier->getId();

        $eventIds = $this->offerRelationsService->getByPlace(
            $placeId
        );

        $cdbXmlDocuments = array();

        foreach ($eventIds as $eventId) {
            $eventCdbXml = $this->realRepository->get($eventId);

            $event = EventItemFactory::createEventFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $eventCdbXml->getCdbXml()
            );

            $location = $event->getLocation();
            $address = $location->getAddress();
            $physicalAddress = $address->getPhysicalAddress();

            $category = $this->findFlandersRegion($physicalAddress);
            $this->updateFlandersRegionCategories($event, $category);

            // Return a new CdbXmlDocument.
            $cdbXmlDocuments[] = $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($event);
        }

        return $cdbXmlDocuments;
    }
}
