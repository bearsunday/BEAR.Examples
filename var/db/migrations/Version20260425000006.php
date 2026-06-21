<?php

declare(strict_types=1);

namespace BEAR\Kata\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create media table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('media');
        $t->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $t->addColumn('filename', 'string', ['length' => 191]);
        $t->addColumn('mime_type', 'string', ['length' => 64]);
        $t->addColumn('url', 'string', ['length' => 255]);
        $t->addColumn('alt', 'string', ['length' => 255, 'notnull' => false]);
        $t->addColumn('width', 'integer', ['unsigned' => true, 'default' => 0]);
        $t->addColumn('height', 'integer', ['unsigned' => true, 'default' => 0]);
        $t->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('media');
    }
}
