<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

use CultuurNet\UDB3\Cdb\EventItemFactory;

class BroadcastingOfferCdbXmlFilter implements BroadcastingCdbXmlFilterInterface
{

    /**
     * @param CdbXmlDocument $cdbXmlDocument
     * @return boolean
     */
    public function matches(CdbXmlDocument $cdbXmlDocument)
    {
        $matches = false;

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXmlDocument->getCdbXml()
        );

        $labels = $event->getKeywords();

        if (in_array('UDB3 place', $labels)) {
            $matches = true;
        }

        return $matches;
    }
}
