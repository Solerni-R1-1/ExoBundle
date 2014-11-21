<?php

namespace UJM\ExoBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/11/21 10:29:50
 */
class Version20141121102947 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE ujm_exercise_user (
                exercise_id INTEGER NOT NULL, 
                user_id INTEGER NOT NULL, 
                givenUp BOOLEAN NOT NULL, 
                PRIMARY KEY(exercise_id, user_id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_806A5E14E934951A ON ujm_exercise_user (exercise_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_806A5E14A76ED395 ON ujm_exercise_user (user_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE ujm_exercise_user
        ");
    }
}