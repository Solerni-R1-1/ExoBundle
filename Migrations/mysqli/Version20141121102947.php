<?php

namespace UJM\ExoBundle\Migrations\mysqli;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/11/21 10:29:51
 */
class Version20141121102947 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE ujm_exercise_user (
                exercise_id INT NOT NULL, 
                user_id INT NOT NULL, 
                givenUp TINYINT(1) NOT NULL, 
                INDEX IDX_806A5E14E934951A (exercise_id), 
                INDEX IDX_806A5E14A76ED395 (user_id), 
                PRIMARY KEY(exercise_id, user_id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
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