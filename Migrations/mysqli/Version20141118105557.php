<?php

namespace UJM\ExoBundle\Migrations\mysqli;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/11/18 10:56:05
 */
class Version20141118105557 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE ujm_paper_question (
                paper_id INT NOT NULL, 
                question_id INT NOT NULL, 
                ordre INT NOT NULL, 
                INDEX IDX_511B1CF1E6758861 (paper_id), 
                INDEX IDX_511B1CF11E27F6BF (question_id), 
                PRIMARY KEY(paper_id, question_id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE ujm_paper_question 
            ADD CONSTRAINT FK_511B1CF1E6758861 FOREIGN KEY (paper_id) 
            REFERENCES ujm_paper (id)
        ");
        $this->addSql("
            ALTER TABLE ujm_paper_question 
            ADD CONSTRAINT FK_511B1CF11E27F6BF FOREIGN KEY (question_id) 
            REFERENCES ujm_question (id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE ujm_paper_question
        ");
    }
}