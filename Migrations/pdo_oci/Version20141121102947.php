<?php

namespace UJM\ExoBundle\Migrations\pdo_oci;

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
                exercise_id NUMBER(10) NOT NULL, 
                user_id NUMBER(10) NOT NULL, 
                givenUp NUMBER(1) NOT NULL, 
                PRIMARY KEY(exercise_id, user_id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_806A5E14E934951A ON ujm_exercise_user (exercise_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_806A5E14A76ED395 ON ujm_exercise_user (user_id)
        ");
        $this->addSql("
            ALTER TABLE ujm_exercise_user 
            ADD CONSTRAINT FK_806A5E14E934951A FOREIGN KEY (exercise_id) 
            REFERENCES ujm_exercise (id)
        ");
        $this->addSql("
            ALTER TABLE ujm_exercise_user 
            ADD CONSTRAINT FK_806A5E14A76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE ujm_exercise_user
        ");
    }
}