<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel;

use Broadway\Domain\Metadata;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\CdbXmlService\CdbXmlDocument\CdbXmlDocument;
use CultuurNet\UDB3\CdbXmlService\ReadModel\Repository\DocumentMetadataFactoryInterface;
use DateTime;
use RuntimeException;

class OfferDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    /**
     * @param CdbXmlDocument $document
     * @return Metadata
     */
    public function createMetadata(CdbXmlDocument $document)
    {
        $cdbXml = $document->getCdbXml();
        $offer = $this->parseOfferCultureFeedItem($cdbXml);

        $values = [];

        if (!empty($offer->getCreatedBy())) {
            $values['user_nick'] = $offer->getCreatedBy();
        }

        if (!empty($offer->getLastUpdatedBy())) {
            $values['user_mail'] = $offer->getLastUpdatedBy();
        }

        if (!empty($offer->getCdbId())) {
            $values['id'] = $offer->getExternalUrl();
        }

        if (!empty($offer->getLastUpdated())) {
            $requestTime = $this->cdbXmlDatetoTimestamp($offer->getLastUpdated());
            $values['request_time'] = $requestTime;
        }

        return new Metadata($values);
    }

    /**
     * @param string $cdbXml
     * @return CultureFeed_Cdb_Item_Base
     *
     * @throws RuntimeException
     *   When the offer cdbxml can not be parsed.
     */
    private function parseOfferCultureFeedItem($cdbXml)
    {
        $namespaceUri = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

        $simpleXml = new \SimpleXMLElement($cdbXml, 0, false, $namespaceUri);
        $isActor = isset($simpleXml->actor);
        $isEvent = isset($simpleXml->event);

        if ($isActor) {
            $item = ActorItemFactory::createActorFromCdbXml($namespaceUri, $cdbXml);
        } elseif ($isEvent) {
            $item = EventItemFactory::createEventFromCdbXml($namespaceUri, $cdbXml);
        } else {
            throw new RuntimeException('Offer cdbxml is neither an actor nor an event.');
        }

        return $item;
    }

    /**
     * @param string $date
     * @return int
     */
    private function cdbXmlDatetoTimestamp($date)
    {
        $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $date);

        return $datetime->getTimestamp();
    }
}