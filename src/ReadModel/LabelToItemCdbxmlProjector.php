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
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
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

    public function applyMadeVisible(MadeVisible $madeVisible)
    {
        $this->applyVisibility($madeVisible, true);
    }

    public function applyMadeInvisible(MadeInvisible $madeVisible)
    {
        $this->applyVisibility($madeVisible, false);
    }

    /**
     * @param AbstractEvent $labelEvent
     * @param boolean $isVisible
     */
    private function applyVisibility(AbstractEvent $labelEvent, $isVisible)
    {
        $labelName = $labelEvent->getName()->toNative();

        $labelRelations = $this->relationRepository->getLabelRelations($labelEvent->getName());

        foreach ($labelRelations as $labelRelation) {
            $relationDocument = $this->cdbxmlRepository->get($labelRelation->getRelationId());

            if ($labelRelation->getRelationType() === RelationType::EVENT()) {
                $cdbXmlItem = EventItemFactory::createEventFromCdbXml(
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                    $relationDocument->getCdbXml()
                );
            } elseif ($labelRelation->getRelationType() === RelationType::PLACE()) {
                $cdbXmlItem = ActorItemFactory::createActorFromCdbXml(
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                    $relationDocument->getCdbXml()
                );
            } else {
                $this->logger->info(
                    'Can not update visibility for label: ' . $labelName
                    . ' The item with id: ' . $labelRelation->getRelationId()->toNative()
                    . ', has an unsupported type: ' . $labelRelation->getRelationType()->toNative()
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
    }
}
