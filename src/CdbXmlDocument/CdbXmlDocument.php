<?php

namespace CultuurNet\UDB3\CdbXmlService\CdbXmlDocument;

use Broadway\ReadModel\ReadModelInterface;

class CdbXmlDocument implements ReadModelInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $cdbXml;

    /**
     * @param string $id
     * @param string $cdbXml
     */
    public function __construct($id, $cdbXml)
    {
        $this->id = $id;
        $this->cdbXml = $cdbXml;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCdbXml()
    {
        return $this->cdbXml;
    }
}
