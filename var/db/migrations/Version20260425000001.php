<?php

declare(strict_types=1);

namespace MyVendor\Cms\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create authors table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('authors');
        $t->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $t->addColumn('name', 'string', ['length' => 100]);
        $t->addColumn('email', 'string', ['length' => 191]);
        $t->addColumn('bio', 'text', ['notnull' => true, 'default' => '']);
        $t->setPrimaryKey(['id']);
        $t->addUniqueIndex(['email'], 'uq_authors_email');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('authors');
    }
}
