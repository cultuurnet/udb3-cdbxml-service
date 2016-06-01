<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use CultuurNet\UDB3\CdbXmlService\Events\PlaceProjectedToCdbXml;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentEventFactoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Offer\Events\AbstractEventWithIri;

class OfferEventFactory implements DocumentEventFactoryInterface
{
    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @param IriGeneratorInterface $iriGenerator
     */
    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return AbstractEventWithIri
     */
    public function createEvent(CdbXmlDocument $cdbXmlDocument)
    {
        return new PlaceProjectedToCdbXml(
            $this->iriGenerator->iri('place/' . $cdbXmlDocument->getId())
        );
    }
}
