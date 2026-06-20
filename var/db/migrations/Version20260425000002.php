<?php

declare(strict_types=1);

namespace BEAR\Examples\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('categories');
        $t->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $t->addColumn('slug', 'string', ['length' => 100]);
        $t->addColumn('name', 'string', ['length' => 100]);
        $t->addColumn('description', 'text', ['notnull' => false]);
        $t->addColumn('parent_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        $t->setPrimaryKey(['id']);
        $t->addUniqueIndex(['slug'], 'uq_categories_slug');
        $t->addForeignKeyConstraint('categories', ['parent_id'], ['id'], ['onDelete' => 'SET NULL'], 'fk_categories_parent');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('categories');
    }
}
