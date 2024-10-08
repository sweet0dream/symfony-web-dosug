<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240622081502 extends AbstractMigration
{
    private string $tableItemStatus = 'item_status';

    public function getDescription(): string
    {
        return 'Added item status table';
    }

    public function up(Schema $schema): void
    {
        $tableItemStatus = $schema->createTable($this->tableItemStatus);
        $tableItemStatus->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableItemStatus->addColumn('item_id', Types::BIGINT)->setNotnull(true);
        $tableItemStatus->addColumn('active', Types::BOOLEAN)->setDefault(false)->setNotnull(true);
        $tableItemStatus->addColumn('premium', Types::BOOLEAN)->setDefault(false)->setNotnull(true);
        $tableItemStatus->addColumn('premium_priority', Types::INTEGER)->setDefault(null)->setNotnull(false);
        $tableItemStatus->addColumn('realy', Types::BOOLEAN)->setDefault(false)->setNotnull(true);
        $tableItemStatus
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['item_id'], 'UNIQ_FDF910D3126F525E')
            ->addForeignKeyConstraint(
                $schema->getTable('item'),
                ['item_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'],
                'FK_FDF910D3126F525E'
            );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItemStatus);
    }
}
