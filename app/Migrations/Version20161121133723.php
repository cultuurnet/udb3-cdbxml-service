<?php

namespace CultuurNet\UDB3\CdbXmlService\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161121133723 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->changeColumnName('uuid_col', 'labelName');
        $this->changeColumnName('offerType', 'relationType');
        $this->changeColumnName('offerId', 'relationId');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Converting back down would loose data if the size was changed.
        $this->changeColumnName('labelName', 'uuid_col');
        $this->changeColumnName('relationType', 'offerType');
        $this->changeColumnName('relationId', 'offerId');
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function changeColumnName($oldName, $newName)
    {
        $this->connection->exec(
            "ALTER TABLE labels_relations CHANGE $oldName $newName VARCHAR(255)"
        );
    }
}
