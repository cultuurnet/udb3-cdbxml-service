<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\Specification;

use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocumentParser;

class PlaceCdbXmlDocumentSpecification implements CdbXmlDocumentSpecificationInterface
{
    /**
     * @var ActorCdbXmlDocumentSpecification
     */
    private $actorCdbXmlDocumentSpecification;

    /**
     * @var CdbXmlDocumentParser
     */
    private $parser;

    public function __construct()
    {
        $this->parser = new CdbXmlDocumentParser();
        $this->actorCdbXmlDocumentSpecification = new ActorCdbXmlDocumentSpecification(
            $this->parser
        );
    }

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return bool
     */
    public function isSatisfiedBy(CdbXmlDocument $cdbXmlDocument)
    {
        $isActor = $this->actorCdbXmlDocumentSpecification->isSatisfiedBy($cdbXmlDocument);

        if ($isActor) {
            $actor = ActorItemFactory::createActorFromCdbXml(
                'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                $cdbXmlDocument->getCdbXml()
            );

            $categories = $actor->getCategories();

            return
              $categories instanceof \CultureFeed_Cdb_Data_CategoryList &&
              $categories->hasCategory('8.15.0.0.0');
        }

        return $isActor;
    }
}
