<?php

namespace CultuurNet\UDB3\CdbXmlService\Relations\Place;

interface RepositoryInterface
{
    public function storeRelations($placeId, $organizerId);

    public function removeRelations($placeId);

    public function getPlacesOrganizedByOrganizer($organizerId);
}
