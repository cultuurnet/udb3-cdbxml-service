<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\FlandersRegionCategoryServiceInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;

class FlandersRegionOrganizerCdbXmlProjector extends AbstractCdbXmlProjector
{
    /**
     * @var FlandersRegionCategoryServiceInterface
     */
    private $categories;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param FlandersRegionCategoryServiceInterface $categories
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        FlandersRegionCategoryServiceInterface $categories
    ) {
        parent::__construct($documentRepository);

        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->categories = $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlers()
    {
        return [
            OrganizerCreated::class => 'applyFlandersRegionToOrganizer',
            OrganizerImportedFromUDB2::class => 'applyFlandersRegionToOrganizer',
            OrganizerUpdatedFromUDB2::class => 'applyFlandersRegionToOrganizer',
        ];
    }

    /**
     * @param OrganizerCreated | OrganizerImportedFromUDB2 | OrganizerUpdatedFromUDB2 $payload
     *
     * @return CdbXmlDocument[]
     */
    public function applyFlandersRegionToOrganizer($payload)
    {
        $organizerId = (get_class($payload) == OrganizerCreated::class) ? $payload->getOrganizerId() : $payload->getActorId();

        $organizerCdbXmlDocument = $this->getCdbXmlDocument($organizerId);

        $organizer = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $organizerCdbXmlDocument->getCdbXml()
        );

        $contactInfo = $organizer->getContactInfo();

        if (empty($contactInfo)) {
            $this->logger->error("no contactinfo found in organizer ({$organizerId})");
            return;
        }

        $addresses = $contactInfo->getAddresses();

        if (empty($addresses)) {
            $this->logger->error("no address found in organizer contactinfo ({$organizerId})");
            return;
        }

        /* @var \CultureFeed_Cdb_Data_Address $address */
        $address = $addresses[0];

        $physicalAddress = $address->getPhysicalAddress();

        if (empty($physicalAddress)) {
            $this->logger->error("no physical address found in organizer address ({$organizerId})");
            return;
        }

        $category = $this->categories->findFlandersRegionCategory($physicalAddress);
        $this->categories->updateFlandersRegionCategories($organizer, $category);

        // Return a new CdbXmlDocument.
        yield $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($organizer);
    }
}
