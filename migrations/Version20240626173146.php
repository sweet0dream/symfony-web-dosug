<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240626173146 extends AbstractMigration
{
    private string $tableUserHash = 'user_hash';
    public function getDescription(): string
    {
        return 'Added user hash table';
    }

    public function up(Schema $schema): void
    {
        $tableUserHash = $schema->createTable($this->tableUserHash);
        $tableUserHash->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableUserHash->addColumn('user_id', Types::BIGINT)->setDefault(null);
        $tableUserHash->addColumn('value', Types::STRING)->setNotnull(true);
        $tableUserHash->addColumn('login_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableUserHash
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['user_id'], 'UNIQ_AB392E71A76ED395')
            ->addForeignKeyConstraint(
                $schema->getTable('user'),
                ['user_id'],
                ['id'],
                ['onDelete' => null, 'onUpdate' => 'CASCADE'],
                'FK_AB392E71A76ED395'
            )
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableUserHash);
    }
}
