<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20250420065312 extends AbstractMigration
{
    private string $tableItemWork = 'item_work';
    private const string SQL_GET_ITEM_IDS = 'SELECT id FROM item';
    public function getDescription(): string
    {
        return 'Added item work table';
    }

    public function up(Schema $schema): void
    {
        $tableItemWork = $schema->createTable($this->tableItemWork);
        $tableItemWork->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableItemWork->addColumn('item_id', Types::BIGINT)->setNotnull(true);
        $tableItemWork->addColumn('from_time', Types::INTEGER)->setNotnull(true)->setDefault(0);
        $tableItemWork->addColumn('to_time', Types::INTEGER)->setNotnull(true)->setDefault(0);
        $tableItemWork->addColumn('is_force', Types::BOOLEAN)->setNotnull(true)->setDefault(false);
        $tableItemWork
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['item_id'], 'UNIQ_9B7E2C7B126F525E')
        ;
    }

    public function postUp(Schema $schema): void
    {
        foreach (array_map(
            fn($item) => $item['id'],
            $this->connection->fetchAllAssociative(self::SQL_GET_ITEM_IDS)
        ) as $itemId) {
            $this->connection->insert(
                $this->tableItemWork,
                ['item_id' => $itemId]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItemWork);
    }
}
