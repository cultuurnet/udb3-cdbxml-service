<?php

namespace CultuurNet\UDB3\CdbXmlService\ReadModel\Repository;

interface OfferRelationsServiceInterface
{
    /**
     * @param string $organizerId
     * @return string[]
     */
    public function getByOrganizer($organizerId);

    /**
     * @param string $placeId
     * @return string[]
     */
    public function getByPlace($placeId);
}
