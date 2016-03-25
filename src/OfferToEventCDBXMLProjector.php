<?php

namespace CultuurNet\UDB3\CDBXMLService;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;

/**
 * Class OfferToEventCDBXMLProjector
 *  This projector takes UDB3 domain messages and projects them to CDBXML and
 *  then publishes the changes to a public URL.
 *
 * @package CultuurNet\UDB3\CDBXMLService
 */
class OfferToEventCDBXMLProjector implements EventListenerInterface
{
    /**
     * OfferToEventCDBXMLProjector constructor.
     */
    public function __construct(
        DocumentRepositoryInterface $documentRepositoryInterface,
        CDBXMLPublisherInterface $CDBXMLPublisher
    ) {
    }

    public function handle(DomainMessage $domainMessage)
    {
        // TODO: Implement handle() method.
    }


}
