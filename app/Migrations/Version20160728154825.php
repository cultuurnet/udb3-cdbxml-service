<?php

namespace CultuurNet\UDB3\CdbXmlService\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160728154825 extends AbstractMigration
{
    const LABELS_RELATIONS_TABLE = 'labels_relations';

    const UUID_COLUMN = 'uuid_col';
    const OFFER_TYPE_COLUMN = 'offerType';
    const OFFER_ID_COLUMN = 'offerId';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->createRelationsRepository($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::LABELS_RELATIONS_TABLE);
    }

    /**
     * @param Schema $schema
     */
    private function createRelationsRepository(Schema $schema)
    {
        $table = $schema->createTable(self::LABELS_RELATIONS_TABLE);

        $table->addColumn(self::UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::OFFER_TYPE_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::OFFER_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex(
            [
                self::UUID_COLUMN,
                self::OFFER_TYPE_COLUMN,
                self::OFFER_ID_COLUMN,
            ]
        );
    }
}
