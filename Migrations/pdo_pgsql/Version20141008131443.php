<?php

namespace UJM\ExoBundle\Migrations\pdo_pgsql;

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
            ALTER TABLE ujm_type_qcm ALTER id 
            DROP DEFAULT
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            CREATE SEQUENCE ujm_type_qcm_id_seq
        ");
        $this->addSql("
            SELECT setval(
                'ujm_type_qcm_id_seq', 
                (
                    SELECT MAX(id) 
                    FROM ujm_type_qcm
                )
            )
        ");
        $this->addSql("
            ALTER TABLE ujm_type_qcm ALTER id 
            SET 
                DEFAULT nextval('ujm_type_qcm_id_seq')
        ");
    }
}