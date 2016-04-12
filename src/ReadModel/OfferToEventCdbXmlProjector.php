<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlPublisherInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentRepositoryInterface;

/**
 * Class OfferToEventCdbXmlProjector
 * This projector takes UDB3 domain messages, projects them to CdbXml and then
 * publishes the changes to a public URL.
 *
 * @package CultuurNet\UDB3\CdbXmlService
 */
class OfferToEventCdbXmlProjector implements EventListenerInterface
{
    /**
     * OfferToEventCdbXmlProjector constructor.
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepositoryInterface,
        CdbXmlPublisherInterface $CDBXMLPublisher
    ) {
    }

    public function handle(DomainMessage $domainMessage)
    {
        // TODO: Implement handle() method.
    }


}
