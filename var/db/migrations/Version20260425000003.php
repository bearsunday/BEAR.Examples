<?php

declare(strict_types=1);

namespace BEAR\Kata\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tags table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('tags');
        $t->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $t->addColumn('slug', 'string', ['length' => 100]);
        $t->addColumn('name', 'string', ['length' => 100]);
        $t->setPrimaryKey(['id']);
        $t->addUniqueIndex(['slug'], 'uq_tags_slug');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('tags');
    }
}
