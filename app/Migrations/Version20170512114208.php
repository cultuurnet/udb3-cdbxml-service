<?php

namespace CultuurNet\UDB3\CdbXmlService\Migrations;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170512114208 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->getLabelsRelationsTable($schema)
            ->addColumn(SchemaConfigurator::IMPORTED, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->getLabelsRelationsTable($schema)
            ->dropColumn(SchemaConfigurator::IMPORTED);
    }

    private function getLabelsRelationsTable(Schema $schema)
    {
        return $schema->getTable('labels_relations');
    }
}