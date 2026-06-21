<?php

declare(strict_types=1);

namespace BEAR\Kata\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article_tags join table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('article_tags');
        $t->addColumn('article_id', 'integer', ['unsigned' => true]);
        $t->addColumn('tag_id', 'integer', ['unsigned' => true]);
        $t->setPrimaryKey(['article_id', 'tag_id']);
        $t->addIndex(['tag_id'], 'idx_article_tags_tag_id');
        $t->addForeignKeyConstraint('articles', ['article_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_article_tags_article');
        $t->addForeignKeyConstraint('tags', ['tag_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_article_tags_tag');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('article_tags');
    }
}
