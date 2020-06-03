<?php

namespace CultuurNet\UDB3\CdbXmlService\Doctrine\DBAL;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

interface SchemaConfiguratorInterface
{
    public function configure(AbstractSchemaManager $schemaManager);
}
