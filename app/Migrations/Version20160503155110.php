<?php

namespace CultuurNet\UDB3\CdbXmlService\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160503155110 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // @see \CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository
        $table = $schema->createTable('event_relations');

        $table->addColumn(
            'event',
            'string',
            array('length' => 36, 'notnull' => false)
        );
        $table->addColumn(
            'organizer',
            'string',
            array('length' => 36, 'notnull' => false)
        );
        $table->addColumn(
            'place',
            'string',
            array('length' => 36, 'notnull' => false)
        );

        $table->setPrimaryKey(array('event'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('event_relations');
    }
}
