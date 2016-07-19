<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
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
        $organizerCdbXml = $this->getCdbXmlDocument(
            (get_class($payload) == OrganizerCreated::class) ? $payload->getOrganizerId() : $payload->getActorId()
        );

        $organizer = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $organizerCdbXml->getCdbXml()
        );

        $contactInfo = $organizer->getContactInfo();
        $addresses = $contactInfo->getAddresses();
        /* @var \CultureFeed_Cdb_Data_Address $address */
        $address = $addresses[0];
        $physicalAddress = $address->getPhysicalAddress();

        $category = $this->categories->findFlandersRegionCategory($physicalAddress);
        $this->categories->updateFlandersRegionCategories($organizer, $category);

        // Return a new CdbXmlDocument.
        yield $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($organizer);
    }
}
