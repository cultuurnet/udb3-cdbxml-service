<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;

abstract class AbstractCdbXmlProjector implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    /**
     * AbstractCdbXmlProjector constructor.
     *
     * @param \CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface $documentRepository
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepository
    ) {
        $this->documentRepository = $documentRepository;
        $this->logger = new NullLogger();
    }

    /**
     * @param string $id
     * @return CdbXmlDocument
     * @throws RuntimeException
     */
    public function getCdbXmlDocument($id)
    {
        $cdbXmlDocument = $this->documentRepository->get($id);

        if ($cdbXmlDocument == null) {
            throw new RuntimeException(
                'No document found for id ' . $id
            );
        }

        return $cdbXmlDocument;
    }

    abstract public function getHandlers();

    /**
     * {@inheritdoc}
     */
    public function handle(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $handlers = $this->getHandlers();

        $this->logger->info('found message ' . $payloadClassName . ' in ' . get_class($this));

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];

            try {
                $this->logger->info(
                    'handling message ' . $payloadClassName . ' using ' . $handlers[$payloadClassName]
                );

                $cdbXmlDocuments = $this->{$handler}($payload);

                foreach ($cdbXmlDocuments as $cdbXmlDocument) {
                    $this->documentRepository->save($cdbXmlDocument);
                }
            } catch (Exception $exception) {
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
}
