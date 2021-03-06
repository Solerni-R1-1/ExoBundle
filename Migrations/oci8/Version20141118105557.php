<?php

namespace UJM\ExoBundle\Migrations\oci8;

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
                paper_id NUMBER(10) NOT NULL, 
                question_id NUMBER(10) NOT NULL, 
                ordre NUMBER(10) NOT NULL, 
                PRIMARY KEY(paper_id, question_id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_511B1CF1E6758861 ON ujm_paper_question (paper_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_511B1CF11E27F6BF ON ujm_paper_question (question_id)
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