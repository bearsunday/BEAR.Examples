<?php

declare(strict_types=1);

namespace MyVendor\Cms\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create articles table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('articles');
        $t->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $t->addColumn('slug', 'string', ['length' => 191]);
        $t->addColumn('title', 'string', ['length' => 255]);
        $t->addColumn('body', 'text', ['length' => 65535]);
        $t->addColumn('excerpt', 'text', ['notnull' => false]);
        $t->addColumn('status', 'string', ['length' => 16, 'default' => 'draft']);
        $t->addColumn('published_at', 'datetime', ['notnull' => false]);
        $t->addColumn('author_id', 'integer', ['unsigned' => true]);
        $t->addColumn('category_id', 'integer', ['unsigned' => true]);
        $t->setPrimaryKey(['id']);
        $t->addUniqueIndex(['slug'], 'uq_articles_slug');
        $t->addIndex(['author_id'], 'idx_articles_author_id');
        $t->addIndex(['category_id'], 'idx_articles_category_id');
        $t->addIndex(['status', 'published_at'], 'idx_articles_status_published');
        $t->addForeignKeyConstraint('authors', ['author_id'], ['id'], ['onDelete' => 'RESTRICT'], 'fk_articles_author');
        $t->addForeignKeyConstraint('categories', ['category_id'], ['id'], ['onDelete' => 'RESTRICT'], 'fk_articles_category');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('articles');
    }
}
