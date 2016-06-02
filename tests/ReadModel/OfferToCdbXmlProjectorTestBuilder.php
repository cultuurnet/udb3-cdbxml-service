<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Offer\OfferType;

class OfferToCdbXmlProjectorTestBuilder
{
    /**
     * @var OfferType
     */
    private $offerType;

    /**
     * @var array
     */
    private $events;

    /**
     * @var Metadata
     */
    private $metaData;

    /**
     * @var string[]
     */
    private $expectedCdbXmlFiles;

    /**
     * @param Metadata $defaultMetadata
     */
    public function __construct(Metadata $defaultMetadata)
    {
        $this->metaData = $defaultMetadata;
        $this->events = [];
        $this->expectedCdbXmlFiles = [];
    }

    /**
     * @param OfferType $offerType
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    public function given(OfferType $offerType)
    {
        $c = clone $this;
        $c->offerType = $offerType;
        return $c;
    }

    /**
     * @param $event
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    public function apply($event)
    {
        $c = clone $this;
        $c->events[] = $event;
        return $c;
    }

    /**
     * @param Metadata $metadata
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    public function withMetadata(Metadata $metadata)
    {
        $c = clone $this;
        $c->metaData = $metadata;
        return $c;
    }

    /**
     * @param string $requestTime
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    public function at($requestTime)
    {
        $c = clone $this;

        $newMetadata = $this->metaData->kv('request_time', $requestTime);
        $c->metaData = $this->metaData->merge($newMetadata);

        return $c;
    }

    /**
     * @param $expectedCdbXmlFile
     * @return OfferToCdbXmlProjectorTestBuilder
     */
    public function expect($expectedCdbXmlFile)
    {
        $c = clone $this;
        $c->expectedCdbXmlFiles[] = $expectedCdbXmlFile;
        return $c;
    }

    /**
     * @return OfferType
     */
    public function getOfferType()
    {
        return $this->offerType;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return metaData
     */
    public function getMetadata()
    {
        return $this->metaData;
    }

    /**
     * @return string[]
     */
    public function getExpectedCdbXmlFiles()
    {
        return $this->expectedCdbXmlFiles;
    }

    /**
     * @return array
     */
    public function finish()
    {
        if (is_null($this->offerType)) {
            throw new \RuntimeException('Offer type not set.');
        }

        return [
            $this->offerType,
            $this->events,
            $this->metaData,
            $this->expectedCdbXmlFiles,
        ];
    }
}
