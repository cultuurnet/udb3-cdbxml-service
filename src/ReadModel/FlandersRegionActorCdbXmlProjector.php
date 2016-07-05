<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;

/**
 * Class FlandersRegionActorCdbXmlProjector
 * This projector takes UDB3 domain messages, projects additional
 * flanders region categories to CdbXml and then
 * publishes the changes to a public URL.
 */
class FlandersRegionActorCdbXmlProjector extends FlandersRegionAbstractCdbXmlProjector
{
    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;


    /**
     * FlandersRegionCdbXmlProjector constructor.
     *
     * @param \CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface $documentRepository
     * @param \CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
    ) {
        parent::__construct($documentRepository);

        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlers()
    {
        return [
            OrganizerCreated::class => 'applyFlandersRegionOrganizerCreatedImportedUpdated',
            OrganizerImportedFromUDB2::class => 'applyFlandersRegionOrganizerCreatedImportedUpdated',
            OrganizerUpdatedFromUDB2::class => 'applyFlandersRegionOrganizerCreatedImportedUpdated',
        ];
    }

    /**
     * @param OrganizerCreated | OrganizerImportedFromUDB2 | OrganizerUpdatedFromUDB2 $payload
     *
     * @return CdbXmlDocument[]
     */
    public function applyFlandersRegionOrganizerCreatedImportedUpdated($payload)
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

        $category = $this->findFlandersRegion($physicalAddress);
        $this->updateFlandersRegionCategories($organizer, $category);

        // Return a new CdbXmlDocument.
        return array(
            $this->cdbXmlDocumentFactory->fromCulturefeedCdbItem($organizer),
        );
    }
}
