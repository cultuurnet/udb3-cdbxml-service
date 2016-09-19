<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\Events\AbstractEvent;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class LabelToItemCdbxmlProjector implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    private $cdbxmlRepository;

    /**
     * @var ReadRepositoryInterface
     */
    private $relationRepository;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * LabelToActorCdbxmlProjector constructor.
     * @param DocumentRepositoryInterface $cdbxmlRepository
     * @param ReadRepositoryInterface $relationRepository
     * @param CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
     */
    public function __construct(
        DocumentRepositoryInterface $cdbxmlRepository,
        ReadRepositoryInterface $relationRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
    ) {
        $this->cdbxmlRepository = $cdbxmlRepository;
        $this->relationRepository = $relationRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
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
            MadeVisible::class => 'applyMadeVisible',
            MadeInvisible::class => 'applyMadeInvisible',
        ];

        $this->logger->info('found message ' . $payloadClassName . ' in ' . get_class());

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];

            try {
                $this->logger->info(
                    'handling message ' . $payloadClassName . ' using ' .
                    $handlers[$payloadClassName] . ' in ' . get_class()
                );

                $this->{$handler}($payload, $domainMessage);
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Handle error for uuid=' . $domainMessage->getId()
                    . ' for type ' . $domainMessage->getType()
                    . ' recorded on ' .$domainMessage->getRecordedOn()->toString()
                    . 'exception' . get_class($exception)
                    . ' message' . $exception->getMessage()
                );
            }
        } else {
            $this->logger->info('no handler found for message ' . $payloadClassName);
        }
    }

    public function applyMadeVisible(MadeVisible $madeVisible, DomainMessage $domainMessage)
    {
        $this->applyVisibility($madeVisible, $domainMessage, true);
    }

    public function applyMadeInvisible(MadeInvisible $madeVisible, DomainMessage $domainMessage)
    {
        $this->applyVisibility($madeVisible, $domainMessage, false);
    }

    /**
     * @param AbstractEvent $labelEvent
     * @param DomainMessage $domainMessage
     * @param boolean $isVisible
     */
    private function applyVisibility(AbstractEvent $labelEvent, DomainMessage $domainMessage, $isVisible)
    {
        $labelName = $this->getLabelName($domainMessage);

        if ($labelName) {
            $offerLabelRelations = $this->relationRepository->getOfferLabelRelations($labelEvent->getUuid());

            foreach ($offerLabelRelations as $offerRelation) {
                $offerDocument = $this->cdbxmlRepository->get($offerRelation->getOfferId());

                if ($offerRelation->getOfferType() === OfferType::EVENT()) {
                    $cdbXmlItem = EventItemFactory::createEventFromCdbXml(
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                        $offerDocument->getCdbXml()
                    );
                } elseif ($offerRelation->getOfferType() === OfferType::PLACE()) {
                    $cdbXmlItem = ActorItemFactory::createActorFromCdbXml(
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                        $offerDocument->getCdbXml()
                    );
                } else {
                    $this->logger->info(
                        'Can not update visibility for label: ' . $labelName
                        . ' The item with id: ' . $offerRelation->getOfferId()
                        . ', has an unsupported type: ' . $offerRelation->getOfferId()
                    );
                }

                if (isset($cdbXmlItem)) {
                    $keywords = $cdbXmlItem->getKeywords(true);

                    /** @var \CultureFeed_Cdb_Data_Keyword $keyword */
                    foreach ($keywords as $keyword) {
                        $keywordLabel = new Label($keyword->getValue());
                        $visibleLabel = new Label($labelName);
                        if ($keywordLabel->equals($visibleLabel)) {
                            $keyword->setVisibility($isVisible);
                        }
                    }

                    $cdbXmlDocument = $this->cdbXmlDocumentFactory
                        ->fromCulturefeedCdbItem($cdbXmlItem);

                    $this->cdbxmlRepository->save($cdbXmlDocument);
                }
            }
        } else {
            $this->logger->info(
                'Could not update visibility for label: ' . $labelEvent->getUuid() .
                ' because metadata has no label name!'
            );
        }
    }

    /**
     * @param DomainMessage $domainMessage
     * @return string|null
     */
    private function getLabelName(DomainMessage $domainMessage)
    {
        $metaDataAsArray = $domainMessage->getMetadata()->serialize();

        return isset($metaDataAsArray['labelName']) ?
            $metaDataAsArray['labelName'] : null;
    }
}
