<?php

namespace CultuurNet\UDB3\CdbXmlService\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170512114208 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('labels_relations')
            ->addColumn('imported', Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('labels_relations')
            ->dropColumn('imported');
    }
}
