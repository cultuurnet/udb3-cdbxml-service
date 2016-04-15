<?php

namespace CultuurNet\UDB3\CdbXmlService;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\CdbXmlDocument;

/**
 * @codeCoverageIgnore
 */
class NullCdbXmlPublisher implements CdbXmlPublisherInterface
{
    /**
     * {@inheritdoc}
     */
    public function publish(
        CdbXmlDocument $CDBXMLDocument,
        DomainMessage $domainMessage
    ) {
        // Intentionally empty.
    }
}
