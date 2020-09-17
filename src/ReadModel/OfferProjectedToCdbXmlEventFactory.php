<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\ActorCdbXmlDocumentSpecification;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\EventCdbXmlDocumentSpecification;
use CultuurNet\UDB3\CdbXmlService\Events\AbstractOfferProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\EventProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentEventFactoryInterface;
use CultuurNet\UDB3\Offer\Events\AbstractEventWithIri;

class OfferProjectedToCdbXmlEventFactory implements DocumentEventFactoryInterface
{
    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var EventCdbXmlDocumentSpecification
     */
    private $eventCdbXmlDocumentSpecification;

    /**
     * @var ActorCdbXmlDocumentSpecification
     */
    private $actorCdbXmlDocumentSpecification;

    /**
     * @param IriGeneratorInterface $iriGenerator
     * @param CdbXmlDocumentParserInterface $cdbXmlDocumentParser
     */
    public function __construct(
        IriGeneratorInterface $iriGenerator,
        CdbXmlDocumentParserInterface $cdbXmlDocumentParser
    ) {
        $this->iriGenerator = $iriGenerator;
        $this->eventCdbXmlDocumentSpecification = new EventCdbXmlDocumentSpecification($cdbXmlDocumentParser);
        $this->actorCdbXmlDocumentSpecification = new ActorCdbXmlDocumentSpecification($cdbXmlDocumentParser);
    }

    public function createEvent(CdbXmlDocument $cdbXmlDocument, $isNew): AbstractOfferProjectedToCdbXml
    {
        if ($this->actorCdbXmlDocumentSpecification->isSatisfiedBy($cdbXmlDocument)) {
            return new PlaceProjectedToCdbXml(
                $cdbXmlDocument->getId(),
                $this->iriGenerator->iri('place/' . $cdbXmlDocument->getId()),
                $isNew
            );
        }

        if ($this->eventCdbXmlDocumentSpecification->isSatisfiedBy($cdbXmlDocument)) {
            return new EventProjectedToCdbXml(
                $cdbXmlDocument->getId(),
                $this->iriGenerator->iri('event/' . $cdbXmlDocument->getId()),
                $isNew
            );
        }

        throw new \LogicException('CdbXmlDocument with id ' . $cdbXmlDocument->getId() . ' is not an offer');
    }
}
