<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultureFeed_Cdb_Data_Address_PhysicalAddress;
use CultureFeed_Cdb_Data_Category;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\NullCdbXmlPublisher;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated as EventMajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated as PlaceMajorInfoUpdated;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Class FlandersRegionCdbXmlProjector
 * This projector takes UDB3 domain messages, projects additional
 * flandersregion categories to CdbXml and then
 * publishes the changes to a public URL.
 */
class FlandersRegionCdbXmlProjector implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * @var CdbXmlPublisherInterface
     */
    private $cdbXmlPublisher;

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;


    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
    ) {

        $this->documentRepository = $documentRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;

        $this->cdbXmlPublisher = new NullCdbXmlPublisher();
        $this->logger = new NullLogger();

    }

    /**
     * {@inheritdoc}
     */
    public function handle(DomainMessage $domainMessage)
    {

        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $handlers = [
            EventCreated::class => 'applyEventFlandersRegionAdded',
            PlaceCreated::class => 'applyPlaceFlandersRegionAdded',
            EventMajorInfoUpdated::class => 'applyEventFlandersRegionAdded',
            PlaceMajorInfoUpdated::class => 'applyPlaceFlandersRegionAdded',
        ];

        $this->logger->info('found message ' . $payloadClassName . ' in OfferToCdbXmlProjector');

        if (isset($handlers[$payloadClassName])) {

            $handler = $handlers[$payloadClassName];

            try {

                $this->logger->info(
                    'handling message ' . $payloadClassName . ' using ' .
                    $handlers[$payloadClassName] . ' in OfferToCdbXmlProjector'
                );

                $cdbXmlDocument = $this->{$handler}($payload);

                $this->documentRepository->save($cdbXmlDocument);
                $this->cdbXmlPublisher->publish($cdbXmlDocument, $domainMessage);

            } catch (\Exception $exception) {

                $this->logger->error(
                    'Handle error for uuid=' . $domainMessage->getId()
                    . ' for type ' . $domainMessage->getType()
                    . ' recorded on ' . $domainMessage->getRecordedOn()
                      ->toString(),
                    [
                      'exception' => get_class($exception),
                      'message' => $exception->getMessage(),
                    ]
                );

            }

        } else {
            $this->logger->info('no handler found for message ' . $payloadClassName);
        }

    }

    /**
     * @param EventMajorInfoUpdated | EventCreated $payload
     *
     * @return CdbXmlDocument
     */
    private function applyEventFlandersRegionAdded($payload)
    {

        $eventCdbXml = $this->getCdbXmlDocument(
            (get_class($payload) == EventMajorInfoUpdated::class) ? $payload->getItemId() : $payload->getEventId()
        );

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
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($event);

    }

    /**
     * @param PlaceMajorInfoUpdated | PlaceCreated $payload
     *
     * @return CdbXmlDocument
     */
    private function applyPlaceFlandersRegionAdded($payload)
    {

        $placeCdbXml = $this->getCdbXmlDocument(
            $payload->getPlaceId()
        );

        $place = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $placeCdbXml->getCdbXml()
        );

        $contactInfo = $place->getContactInfo();
        $addresses = $contactInfo->getAddresses();
        /* @var \CultureFeed_Cdb_Data_Address $address */
        $address = $addresses[0];
        $physicalAddress = $address->getPhysicalAddress();

        $category = $this->findFlandersRegion($physicalAddress);
        $this->updateFlandersRegionCategories($place, $category);

        // Return a new CdbXmlDocument.
        return $this->cdbXmlDocumentFactory
            ->fromCulturefeedCdbItem($place);

    }

    /**
     * @param CultureFeed_Cdb_Data_Address_PhysicalAddress $physicalAddress
     *
     * @return CultureFeed_Cdb_Data_Category|null
     */
    private function findFlandersRegion(CultureFeed_Cdb_Data_Address_PhysicalAddress $physicalAddress)
    {

        $file = 'config/term.xml';

        if (file_exists($file)) {

            $city = $physicalAddress->getCity();
            $zip = $physicalAddress->getZip();

            $xml = file_get_contents($file);
            $terms = new \SimpleXMLElement($xml);
            $terms->registerXPathNamespace('c', 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL');
            $result = $terms->xpath(
                '//c:term[@domain=\'' . CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION . '\' and '
                . 'contains(@label, \'' . $zip . '\') and '
                . 'contains(@label, \'' . $city . '\')]'
            );

            if (count($result)) {

                $category = new CultureFeed_Cdb_Data_Category(
                    CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION,
                    $result[0]['id'],
                    $result[0]['label']
                );

                return $category;

            }

        }

    }

    /**
     * @param string $id
     * @return CdbXmlDocument
     * @throws RuntimeException
     */
    private function getCdbXmlDocument($id)
    {
        $cdbXmlDocument = $this->documentRepository->get($id);

        if ($cdbXmlDocument == null) {
            throw new RuntimeException(
                'No document found for id ' . $id
            );
        }

        return $cdbXmlDocument;
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param CultureFeed_Cdb_Data_Category|null $newCategory
     */
    private function updateFlandersRegionCategories(CultureFeed_Cdb_Item_Base $item, CultureFeed_Cdb_Data_Category $newCategory = null)
    {

        $updated = false;
        foreach ($item->getCategories() as $key => $category) {

            if ($category->getType() == CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_FLANDERS_REGION) {

                if ($newCategory && !$updated) {
                    $category->setId($newCategory->getId());
                    $category->setName($newCategory->getName());
                    $updated = true;
                } else {
                    $item->getCategories()->delete($key);
                }
            }
        }

        if (!$updated && $newCategory) {
            $item->getCategories()->add($newCategory);
        }

    }

    /**
     * @param CdbXmlPublisherInterface $cdbXmlPublisher
     * @return OfferToCdbXmlProjector
     */
    public function withCdbXmlPublisher(CdbXmlPublisherInterface $cdbXmlPublisher)
    {
        $c = clone $this;
        $c->cdbXmlPublisher = $cdbXmlPublisher;
        return $c;
    }
}
