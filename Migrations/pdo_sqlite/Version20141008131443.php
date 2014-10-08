<?php

namespace UJM\ExoBundle\Migrations\pdo_sqlite;

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
            DROP INDEX UNIQ_4C21382C77153098
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__ujm_type_qcm AS 
            SELECT id, 
            value, 
            code 
            FROM ujm_type_qcm
        ");
        $this->addSql("
            DROP TABLE ujm_type_qcm
        ");
        $this->addSql("
            CREATE TABLE ujm_type_qcm (
                id INTEGER NOT NULL, 
                value VARCHAR(255) NOT NULL, 
                code INTEGER NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            INSERT INTO ujm_type_qcm (id, value, code) 
            SELECT id, 
            value, 
            code 
            FROM __temp__ujm_type_qcm
        ");
        $this->addSql("
            DROP TABLE __temp__ujm_type_qcm
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_4C21382C77153098 ON ujm_type_qcm (code)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP INDEX UNIQ_4C21382C77153098
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__ujm_type_qcm AS 
            SELECT id, 
            value, 
            code 
            FROM ujm_type_qcm
        ");
        $this->addSql("
            DROP TABLE ujm_type_qcm
        ");
        $this->addSql("
            CREATE TABLE ujm_type_qcm (
                id INTEGER NOT NULL, 
                value VARCHAR(255) NOT NULL, 
                code INTEGER NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            INSERT INTO ujm_type_qcm (id, value, code) 
            SELECT id, 
            value, 
            code 
            FROM __temp__ujm_type_qcm
        ");
        $this->addSql("
            DROP TABLE __temp__ujm_type_qcm
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_4C21382C77153098 ON ujm_type_qcm (code)
        ");
    }
}