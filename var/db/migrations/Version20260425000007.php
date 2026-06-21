<?php

declare(strict_types=1);

namespace BEAR\Kata\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425000007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create auth identities table';
    }

    public function up(Schema $schema): void
    {
        $t = $schema->createTable('auth_identities');
        $t->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $t->addColumn('provider', 'string', ['length' => 32]);
        $t->addColumn('subject', 'string', ['length' => 191]);
        $t->addColumn('author_id', 'integer', ['unsigned' => true]);
        $t->addColumn('email', 'string', ['length' => 191]);
        $t->addColumn('name', 'string', ['length' => 100]);
        $t->setPrimaryKey(['id']);
        $t->addUniqueIndex(['provider', 'subject'], 'uq_auth_identities_provider_subject');
        $t->addIndex(['author_id'], 'idx_auth_identities_author_id');
        $t->addForeignKeyConstraint('authors', ['author_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_auth_identities_author');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('auth_identities');
    }
}
