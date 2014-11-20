<?php

namespace UJM\ExoBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/11/18 04:27:07
 */
class Version20141118162701 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            DROP INDEX IDX_82972E4BA76ED395
        ");
        $this->addSql("
            DROP INDEX IDX_82972E4BE934951A
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__ujm_paper AS 
            SELECT id, 
            user_id, 
            exercise_id, 
            num_paper, 
            start, 
            \"end\", 
            ordre_question, 
            archive, 
            date_archive, 
            interupt, 
            mark 
            FROM ujm_paper
        ");
        $this->addSql("
            DROP TABLE ujm_paper
        ");
        $this->addSql("
            CREATE TABLE ujm_paper (
                id INTEGER NOT NULL, 
                user_id INTEGER DEFAULT NULL, 
                exercise_id INTEGER DEFAULT NULL, 
                num_paper INTEGER NOT NULL, 
                start DATETIME NOT NULL, 
                \"end\" DATETIME DEFAULT NULL, 
                ordre_question CLOB DEFAULT NULL, 
                archive BOOLEAN DEFAULT NULL, 
                date_archive DATE DEFAULT NULL, 
                interupt BOOLEAN DEFAULT NULL, 
                mark DOUBLE PRECISION DEFAULT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_82972E4BA76ED395 FOREIGN KEY (user_id) 
                REFERENCES claro_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_82972E4BE934951A FOREIGN KEY (exercise_id) 
                REFERENCES ujm_exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO ujm_paper (
                id, user_id, exercise_id, num_paper, 
                start, \"end\", ordre_question, archive, 
                date_archive, interupt, mark
            ) 
            SELECT id, 
            user_id, 
            exercise_id, 
            num_paper, 
            start, 
            \"end\", 
            ordre_question, 
            archive, 
            date_archive, 
            interupt, 
            mark 
            FROM __temp__ujm_paper
        ");
        $this->addSql("
            DROP TABLE __temp__ujm_paper
        ");
        $this->addSql("
            CREATE INDEX IDX_82972E4BA76ED395 ON ujm_paper (user_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_82972E4BE934951A ON ujm_paper (exercise_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP INDEX IDX_82972E4BA76ED395
        ");
        $this->addSql("
            DROP INDEX IDX_82972E4BE934951A
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__ujm_paper AS 
            SELECT id, 
            user_id, 
            exercise_id, 
            num_paper, 
            start, 
            \"end\", 
            ordre_question, 
            archive, 
            date_archive, 
            interupt, 
            mark 
            FROM ujm_paper
        ");
        $this->addSql("
            DROP TABLE ujm_paper
        ");
        $this->addSql("
            CREATE TABLE ujm_paper (
                id INTEGER NOT NULL, 
                user_id INTEGER DEFAULT NULL, 
                exercise_id INTEGER DEFAULT NULL, 
                num_paper INTEGER NOT NULL, 
                start DATETIME NOT NULL, 
                \"end\" DATETIME DEFAULT NULL, 
                ordre_question CLOB DEFAULT NULL, 
                archive BOOLEAN DEFAULT NULL, 
                date_archive DATE DEFAULT NULL, 
                interupt BOOLEAN DEFAULT NULL, 
                mark INTEGER DEFAULT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_82972E4BA76ED395 FOREIGN KEY (user_id) 
                REFERENCES claro_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
                CONSTRAINT FK_82972E4BE934951A FOREIGN KEY (exercise_id) 
                REFERENCES ujm_exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO ujm_paper (
                id, user_id, exercise_id, num_paper, 
                start, \"end\", ordre_question, archive, 
                date_archive, interupt, mark
            ) 
            SELECT id, 
            user_id, 
            exercise_id, 
            num_paper, 
            start, 
            \"end\", 
            ordre_question, 
            archive, 
            date_archive, 
            interupt, 
            mark 
            FROM __temp__ujm_paper
        ");
        $this->addSql("
            DROP TABLE __temp__ujm_paper
        ");
        $this->addSql("
            CREATE INDEX IDX_82972E4BA76ED395 ON ujm_paper (user_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_82972E4BE934951A ON ujm_paper (exercise_id)
        ");
    }
}