<?php

namespace CultuurNet\UDB3\CDBXMLService\Repository;

use Broadway\ReadModel\ReadModelInterface;

class CDBXMLDocument implements ReadModelInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $cdbxml;

    /**
     * @param string $id
     * @param string $cdbxml
     */
    public function __construct($id, $cdbxml)
    {
        $this->id = $id;
        $this->cdbxml = $cdbxml;
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCDBXML() {
        return $this->cdbxml;
    }
}
