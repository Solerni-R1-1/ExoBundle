<?php

namespace UJM\ExoBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/08/28 04:30:37
 */
class Version20140828163036 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE ujm_response 
            ADD COLUMN mark_used_for_hint INTEGER NOT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP INDEX IDX_A7EC2BC2E6758861
        ");
        $this->addSql("
            DROP INDEX IDX_A7EC2BC2886DEE8F
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__ujm_response AS 
            SELECT id, 
            paper_id, 
            interaction_id, 
            ip, 
            mark, 
            nb_tries, 
            response 
            FROM ujm_response
        ");
        $this->addSql("
            DROP TABLE ujm_response
        ");
        $this->addSql("
            CREATE TABLE ujm_response (
                id INTEGER NOT NULL, 
                paper_id INTEGER DEFAULT NULL, 
                interaction_id INTEGER DEFAULT NULL, 
                ip VARCHAR(255) NOT NULL, 
                mark DOUBLE PRECISION NOT NULL, 
                nb_tries INTEGER NOT NULL, 
                response CLOB DEFAULT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_A7EC2BC2E6758861 FOREIGN KEY (paper_id) 
                REFERENCES ujm_paper (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_A7EC2BC2886DEE8F FOREIGN KEY (interaction_id) 
                REFERENCES ujm_interaction (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO ujm_response (
                id, paper_id, interaction_id, ip, mark, 
                nb_tries, response
            ) 
            SELECT id, 
            paper_id, 
            interaction_id, 
            ip, 
            mark, 
            nb_tries, 
            response 
            FROM __temp__ujm_response
        ");
        $this->addSql("
            DROP TABLE __temp__ujm_response
        ");
        $this->addSql("
            CREATE INDEX IDX_A7EC2BC2E6758861 ON ujm_response (paper_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_A7EC2BC2886DEE8F ON ujm_response (interaction_id)
        ");
    }
}