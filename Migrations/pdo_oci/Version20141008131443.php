<?php

namespace UJM\ExoBundle\Migrations\pdo_oci;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/10/08 01:14:44
 */
class Version20141008131443 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE ujm_type_qcm MODIFY (
                id NUMBER(10) DEFAULT NULL
            )
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE ujm_type_qcm MODIFY (
                id NUMBER(10) DEFAULT NULL
            )
        ");
    }
}