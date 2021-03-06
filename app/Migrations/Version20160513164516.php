<?php

namespace CultuurNet\UDB3\CdbXmlService\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160513164516 extends AbstractMigration
{
    const PLACE_RELATIONS = 'place_relations';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::PLACE_RELATIONS);

        $table->addColumn(
            'place',
            'string',
            array('length' => 36, 'notnull' => false)
        );
        $table->addColumn(
            'organizer',
            'string',
            array('length' => 36, 'notnull' => false)
        );

        $table->setPrimaryKey(array('place'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::PLACE_RELATIONS);
    }
}
