<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240908105614 extends AbstractMigration
{
    private string $tableEvent = 'event';

    public function getDescription(): string
    {
        return 'Added event table';
    }

    public function up(Schema $schema): void
    {
        $tableEvent = $schema->createTable($this->tableEvent);
        $tableEvent->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableEvent->addColumn('user_id', Types::BIGINT)->setDefault(null);
        $tableEvent->addColumn('item_id', Types::BIGINT)->setDefault(null);
        $tableEvent->addColumn('event', Types::TEXT)->setNotnull(true);
        $tableEvent->addColumn('created_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableEvent
            ->setPrimaryKey(['id'])
            ->addIndex(['user_id'], 'IDX_3BAE0AA7A76ED395')
            ->addIndex(['item_id'], 'IDX_3BAE0AA7126F525E')
            ->addForeignKeyConstraint(
                $schema->getTable('user'),
                ['user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null],
                'FK_3BAE0AA7A76ED395'
            )->addForeignKeyConstraint(
                $schema->getTable('item'),
                ['item_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null],
                'FK_3BAE0AA7126F525E'
            )
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableEvent);
    }
}
