<?php

namespace UJM\ExoBundle\Migrations\pdo_sqlite;

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
                paper_id INTEGER NOT NULL, 
                question_id INTEGER NOT NULL, 
                ordre INTEGER NOT NULL, 
                PRIMARY KEY(paper_id, question_id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_511B1CF1E6758861 ON ujm_paper_question (paper_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_511B1CF11E27F6BF ON ujm_paper_question (question_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE ujm_paper_question
        ");
    }
}