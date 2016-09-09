<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParserInterface;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\ActorCdbXmlDocumentSpecification;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification\EventCdbXmlDocumentSpecification;
use CultuurNet\UDB3\CdbXmlService\Events\EventProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentEventFactoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
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

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @param bool $isNew
     * @return AbstractEventWithIri
     */
    public function createEvent(CdbXmlDocument $cdbXmlDocument, $isNew)
    {
        if ($this->actorCdbXmlDocumentSpecification->isSatisfiedBy($cdbXmlDocument)) {
            return new PlaceProjectedToCdbXml(
                $this->iriGenerator->iri('place/' . $cdbXmlDocument->getId()),
                $isNew
            );
        } elseif ($this->eventCdbXmlDocumentSpecification->isSatisfiedBy($cdbXmlDocument)) {
            return new EventProjectedToCdbXml(
                $this->iriGenerator->iri('event/' . $cdbXmlDocument->getId()),
                $isNew
            );
        }

        throw new \LogicException('CdbXmlDocument with id ' . $cdbXmlDocument->getId() . ' is not an offer');
    }
}
