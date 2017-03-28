<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CultureFeed\AddressFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Title;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class OrganizerToActorCdbXmlProjector implements EventListenerInterface, LoggerAwareInterface
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
     * @var AddressFactoryInterface
     */
    private $addressFactory;

    /**
     * @var MetadataCdbItemEnricherInterface
     */
    private $metadataCdbItemEnricher;

    /**
     * @param DocumentRepositoryInterface $documentRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     * @param AddressFactoryInterface $addressFactory
     * @param MetadataCdbItemEnricherInterface $metadataCdbItemEnricher
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory,
        AddressFactoryInterface $addressFactory,
        MetadataCdbItemEnricherInterface $metadataCdbItemEnricher
    ) {
        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->addressFactory = $addressFactory;
        $this->metadataCdbItemEnricher = $metadataCdbItemEnricher;

        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     *
     * @uses applyOrganizerCreated()
     * @uses applyOrganizerCreatedWithUniqueWebsite()
     * @uses applyActorImportedFromUdb2()
     * @uses applyTitleUpdated()
     * @uses applyAddressUpdated()
     * @uses applyContactPointUpdated()
     * @uses applyLabelAdded()
     * @uses applyLabelRemoved()
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $metadata = $domainMessage->getMetadata();

        $handlers = [
            OrganizerCreated::class => 'applyOrganizerCreated',
            OrganizerCreatedWithUniqueWebsite::class => 'applyOrganizerCreatedWithUniqueWebsite',
            OrganizerImportedFromUDB2::class => 'applyActorImportedFromUdb2',
            OrganizerUpdatedFromUDB2::class => 'applyActorImportedFromUdb2',
            TitleUpdated::class => 'applyTitleUpdated',
            AddressUpdated::class => 'applyAddressUpdated',
            ContactPointUpdated::class => 'applyContactPointUpdated',
            LabelAdded::class => 'applyLabelAdded',
            LabelRemoved::class => 'applyLabelRemoved',
        ];

        $this->logger->info('found message ' . $payloadClassName . ' in OrganizerToActorCdbXmlProjector');

        if (isset($handlers[$payloadClassName])) {
            try {
                $this->logger->info(
                    'handling message ' . $payloadClassName . ' using ' .
                    $handlers[$payloadClassName] . ' in OfferToCdbXmlProjector'
                );

                $handler = $handlers[$payloadClassName];
                $cdbXmlDocument = $this->{$handler}($payload, $metadata);

                $this->documentRepository->save($cdbXmlDocument);
            } catch (Exception $exception) {
                // Log any exceptions that occur while projecting events.
                // The exception is passed to context as specified in: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#13-context
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        } else {
            $this->logger->info('no handler found for message ' . $payloadClassName);
        }
    }

    /**
     * @param OrganizerCreated $organizerCreated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyOrganizerCreated(
        OrganizerCreated $organizerCreated,
        Metadata $metadata
    ) {
        $actor = $this->buildActorFromOrganizerEvent($organizerCreated, $metadata);

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

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param OrganizerCreated|OrganizerCreatedWithUniqueWebsite $organizerCreated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyOrganizerCreatedWithUniqueWebsite(
        OrganizerCreatedWithUniqueWebsite $organizerCreated,
        Metadata $metadata
    ) {
        $actor = $this->buildActorFromOrganizerEvent($organizerCreated, $metadata);

        $contactInfo = new \CultureFeed_Cdb_Data_ContactInfo();
        $contactInfo->addUrl(new \CultureFeed_Cdb_Data_Url((string) $organizerCreated->getWebsite()));
        $actor->setContactInfo($contactInfo);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param TitleUpdated $titleUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyTitleUpdated(
        TitleUpdated $titleUpdated,
        Metadata $metadata
    ) {
        $document = $this->documentRepository->get($titleUpdated->getOrganizerId());

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $document->getCdbXml()
        );
        $this->setTitle($actor, $titleUpdated->getTitle());

        $actor = $this->metadataCdbItemEnricher->enrich($actor, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param AddressUpdated $addressUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyAddressUpdated(
        AddressUpdated $addressUpdated,
        Metadata $metadata
    ) {
        $document = $this->documentRepository->get($addressUpdated->getOrganizerId());

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $document->getCdbXml()
        );

        $contactInfo = $actor->getContactInfo();

        // Remove all addresses, then add the updated address.
        // We have to remove all addresses because we don't know which one is
        // the updated one, and organizers can only have one address anyway in
        // udb3.
        foreach ($contactInfo->getAddresses() as $index => $address) {
            $contactInfo->removeAddress($index);
        }
        $address = $this->addressFactory->fromUdb3Address($addressUpdated->getAddress());
        $contactInfo->addAddress($address);

        $actor->setContactInfo($contactInfo);

        $actor = $this->metadataCdbItemEnricher->enrich($actor, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param ContactPointUpdated $contactPointUpdated
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyContactPointUpdated(
        ContactPointUpdated $contactPointUpdated,
        Metadata $metadata
    ) {
        $document = $this->documentRepository->get($contactPointUpdated->getOrganizerId());

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $document->getCdbXml()
        );

        $contactInfo = $actor->getContactInfo();

        $contactInfo->deletePhones();
        $contactInfo->deleteUrls();
        $contactInfo->deleteMails();

        foreach ($contactPointUpdated->getContactPoint()->getPhones() as $phone) {
            $contactInfo->addPhone(new \CultureFeed_Cdb_Data_Phone($phone));
        }

        foreach ($contactPointUpdated->getContactPoint()->getUrls() as $url) {
            $contactInfo->addUrl(new \CultureFeed_Cdb_Data_Url($url));
        }

        foreach ($contactPointUpdated->getContactPoint()->getEmails() as $email) {
            $contactInfo->addMail(new \CultureFeed_Cdb_Data_Mail($email));
        }

        $actor->setContactInfo($contactInfo);

        $actor = $this->metadataCdbItemEnricher->enrich($actor, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param ActorImportedFromUDB2 $actorImportedFromUdb2
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyActorImportedFromUdb2(
        ActorImportedFromUDB2 $actorImportedFromUdb2,
        Metadata $metadata
    ) {
        // Convert the imported CdbXml to a CultureFeed Actor so we can convert
        // it to a different CdbXml format in the CdbXmlDocumentFactory if
        // necessary. (Eg. namespaced to non-namespaced, or 3.2 to 3.3, ...)
        // @todo Remove this hard dependency on ActorItemFactory if possible.
        $actor = ActorItemFactory::createActorFromCdbXml(
            $actorImportedFromUdb2->getCdbXmlNamespaceUri(),
            $actorImportedFromUdb2->getCdbXml()
        );

        $actor = $this->metadataCdbItemEnricher
            ->enrich($actor, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($actor);
    }

    /**
     * @param LabelAdded $labelAdded
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyLabelAdded(LabelAdded $labelAdded, Metadata $metadata)
    {
        return $this->applyLabelEvent($labelAdded, $metadata);
    }

    /**
     * @param LabelRemoved $labelRemoved
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyLabelRemoved(LabelRemoved $labelRemoved, Metadata $metadata)
    {
        return $this->applyLabelEvent($labelRemoved, $metadata);
    }

    /**
     * @param AbstractLabelEvent $labelEvent
     * @param Metadata $metadata
     * @return CdbXmlDocument
     */
    private function applyLabelEvent(AbstractLabelEvent $labelEvent, Metadata $metadata)
    {
        $labelName = (string) $labelEvent->getLabel();

        $document = $this->documentRepository->get($labelEvent->getOrganizerId());
        $organizer = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $document->getCdbXml()
        );

        if ($organizer && $labelName) {
            if ($labelEvent instanceof LabelAdded) {
                $organizer->addKeyword(
                    new \CultureFeed_Cdb_Data_Keyword(
                        $labelName,
                        $labelEvent->getLabel()->isVisible()
                    )
                );
            } else {
                $organizer->deleteKeyword(
                    new \CultureFeed_Cdb_Data_Keyword(
                        $labelName,
                        $labelEvent->getLabel()->isVisible()
                    )
                );
            }
        }

        $organizer = $this->metadataCdbItemEnricher
            ->enrich($organizer, $metadata);

        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($organizer);
    }

    /**
     * @param OrganizerEvent|OrganizerCreated|OrganizerCreatedWithUniqueWebsite $organizerCreationEvent
     *
     * @param Metadata $metadata
     *
     * @return \CultureFeed_Cdb_Item_Actor
     */
    private function buildActorFromOrganizerEvent(
        OrganizerEvent $organizerCreationEvent,
        Metadata $metadata
    ) {
        // Actor.
        $actor = new \CultureFeed_Cdb_Item_Actor();
        $actor->setCdbId($organizerCreationEvent->getOrganizerId());

        // Title
        $this->setTitle($actor, $organizerCreationEvent->getTitle());

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

        // Add metadata like createdby, creationdate, etc to the actor.
        $actor = $this->metadataCdbItemEnricher
            ->enrich($actor, $metadata);

        return $actor;
    }

    /**
     * @param \CultureFeed_Cdb_Item_Actor $actor
     * @param Title $title
     */
    private function setTitle(
        \CultureFeed_Cdb_Item_Actor $actor,
        Title $title
    ) {
        // Details.
        $nlDetail = new \CultureFeed_Cdb_Data_ActorDetail();
        $nlDetail->setLanguage('nl');
        $nlDetail->setTitle($title->toNative());

        $details = new \CultureFeed_Cdb_Data_ActorDetailList();
        $details->add($nlDetail);
        $actor->setDetails($details);
    }
}
