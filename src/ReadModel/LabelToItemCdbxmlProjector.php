<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentFactoryInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\NullCdbXmlPublisher;
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
     * @var CdbXmlPublisherInterface
     */
    private $cdbXmlPublisher;

    /**
     * @var CdbXmlDocumentFactoryInterface
     */
    private $cdbXmlDocumentFactory;

    /**
     * LabelToActorCdbxmlProjector constructor.
     * @param DocumentRepositoryInterface $cdbxmlRepository
     * @param ReadRepositoryInterface $relationRepository
     */
    public function __construct(
        DocumentRepositoryInterface $cdbxmlRepository,
        ReadRepositoryInterface $relationRepository,
        CdbXmlDocumentFactoryInterface $cdbXmlDocumentFactory
    ) {
        $this->cdbxmlRepository = $cdbxmlRepository;
        $this->relationRepository = $relationRepository;
        $this->cdbXmlDocumentFactory = $cdbXmlDocumentFactory;
        $this->cdbXmlPublisher = new NullCdbXmlPublisher();
        $this->logger = new NullLogger();
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

    /**
     * {@inheritdoc}
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $metadata = $domainMessage->getMetadata();

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
                    . ' recorded on ' .$domainMessage->getRecordedOn()->toString(),
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
        $offerLabelRelations = $this->relationRepository->getOfferLabelRelations($labelEvent->getUuid());

        foreach ($offerLabelRelations as $offerRelation) {
            $offerDocument = $this->cdbxmlRepository->get($offerRelation->getRelationId());

            if ($offerRelation->getRelationType() === OfferType::EVENT()) {
                $cdbXmlItem = EventItemFactory::createEventFromCdbXml(
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                    $offerDocument->getCdbXml()
                );
            } elseif ($offerRelation->getRelationType() === OfferType::PLACE()) {
                $cdbXmlItem = ActorItemFactory::createActorFromCdbXml(
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                    $offerDocument->getCdbXml()
                );
            } else {
                $this->logger->info(
                    'Can not update visibility for label: '. $offerRelation->getLabelName()
                    . ' The item with id: ' . $offerRelation->getRelationId()
                    . ', has an unsupported type: ' . $offerRelation->getRelationType()
                );
            }

            if (isset($cdbXmlItem)) {
                $keywords = $cdbXmlItem->getKeywords(true);

                /** @var \CultureFeed_Cdb_Data_Keyword $keyword */
                foreach ($keywords as $keyword) {
                    $keywordLabel = new Label($keyword->getValue());
                    $visibleLabel = new Label((string) $offerRelation->getLabelName());
                    if ($keywordLabel->equals($visibleLabel)) {
                        $keyword->setVisibility($isVisible);
                    }
                }

                $cdbXmlDocument = $this->cdbXmlDocumentFactory
                    ->fromCulturefeedCdbItem($cdbXmlItem);
                
                $this->cdbxmlRepository->save($cdbXmlDocument);
                $this->cdbXmlPublisher->publish($cdbXmlDocument, $domainMessage);
            }
        }
    }
}
