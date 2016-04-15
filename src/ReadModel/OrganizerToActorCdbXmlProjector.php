<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\NullCdbXmlPublisher;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;

class OrganizerToActorCdbXmlProjector implements EventListenerInterface
{
    /**
     * @var CdbXmlPublisherInterface
     */
    private $cdbXmlPublisher;

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var AddressFactoryInterface
     */
    private $addressFactory;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param AddressFactoryInterface $addressFactory
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        AddressFactoryInterface $addressFactory
    ) {
        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->addressFactory = $addressFactory;

        $this->cdbXmlPublisher = new NullCdbXmlPublisher();
    }

    /**
     * @param CdbXmlPublisherInterface $cdbXmlPublisher
     * @return OrganizerToActorCdbXmlProjector
     */
    public function withCdbXmlPublisher(CdbXmlPublisherInterface $cdbXmlPublisher)
    {
        $c = clone $this;
        $c->cdbXmlPublisher = $cdbXmlPublisher;
        return $c;
    }

    /**
     * {@inheritdoc}
     *
     * @uses applyOrganizerCreated()
     * @uses applyActorImportedFromUdb2()
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $handlers = [
            OrganizerCreated::class => 'applyOrganizerCreated',
            OrganizerImportedFromUDB2::class => 'applyActorImportedFromUdb2',
            OrganizerUpdatedFromUDB2::class => 'applyActorImportedFromUdb2',
        ];

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];
            $cdbXmlDocument = $this->{$handler}($payload);

            $this->documentRepository->save($cdbXmlDocument);

            $this->cdbXmlPublisher->publish($cdbXmlDocument, $domainMessage);
        }
    }

    /**
     * @param OrganizerCreated $organizerCreated
     * @return CdbXmlDocument
     */
    private function applyOrganizerCreated(OrganizerCreated $organizerCreated)
    {
        // Actor.
        $actor = new \CultureFeed_Cdb_Item_Actor();
        $actor->setCdbId($organizerCreated->getOrganizerId());

        // Details.
        $nlDetail = new \CultureFeed_Cdb_Data_ActorDetail();
        $nlDetail->setLanguage('nl');
        $nlDetail->setTitle($organizerCreated->getTitle());

        $details = new \CultureFeed_Cdb_Data_ActorDetailList();
        $details->add($nlDetail);
        $actor->setDetails($details);

        // Contact info.
        $contactInfo = new \CultureFeed_Cdb_Data_ContactInfo();

        foreach ($organizerCreated->getAddresses() as $udb3Address) {
            $address = $this->addressFactory->fromUdb3Address($udb3Address);
            $contactInfo->addAddress($address);
        }

        foreach ($organizerCreated->getPhones() as $phone) {
            $contactInfo->addPhone(new \CultureFeed_Cdb_Data_Phone($phone));
        }

        foreach ($organizerCreated->getUrls() as $url) {
            $contactInfo->addUrl(new \CultureFeed_Cdb_Data_Url($url));
        }

        foreach ($organizerCreated->getEmails() as $email) {
            $contactInfo->addMail(new \CultureFeed_Cdb_Data_Mail($email));
        }

        $actor->setContactInfo($contactInfo);

        // Categories.
        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $categoryList->add(
            new \CultureFeed_Cdb_Data_Category(
                'actortype',
                '8.11.0.0.0',
                'Organisator(en)'
            )
        );
        $actor->setCategories($categoryList);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param ActorImportedFromUDB2 $actorImportedFromUdb2
     * @return CdbXmlDocument
     */
    private function applyActorImportedFromUdb2(ActorImportedFromUDB2 $actorImportedFromUdb2)
    {
        // Convert the imported CdbXml to a CultureFeed Actor so we can convert
        // it to a different CdbXml format in the CdbXmlDocumentFactory if
        // necessary. (Eg. namespaced to non-namespaced, or 3.2 to 3.3, ...)
        // @todo Remove this hard dependency on ActorItemFactory if possible.
        $actor = ActorItemFactory::createActorFromCdbXml(
            $actorImportedFromUdb2->getCdbXmlNamespaceUri(),
            $actorImportedFromUdb2->getCdbXml()
        );

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }
}
