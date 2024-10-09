<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240621114620 extends AbstractMigration
{

    private string $tableItem = 'item';

    public function getDescription(): string
    {
        return 'Added item table';
    }

    public function up(Schema $schema): void
    {
        $tableItem = $schema->createTable($this->tableItem);
        $tableItem->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableItem->addColumn('user_id', Types::BIGINT)->setDefault(null);
        $tableItem->addColumn('phone', Types::STRING)->setNotnull(true);
        $tableItem->addColumn('name', Types::STRING)->setNotnull(true);
        $tableItem->addColumn('type', Types::STRING)->setNotnull(true)->setLength(3);
        $tableItem->addColumn('info', Types::TEXT)->setNotnull(true);
        $tableItem->addColumn('service', Types::TEXT)->setNotnull(true);
        $tableItem->addColumn('price', Types::TEXT)->setNotnull(true);
        $tableItem->addColumn('created_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItem->addColumn('updated_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItem->addColumn('toped_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItem
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['phone'], 'UNIQ_1F1B251E444F97DD')
            ->addIndex(['user_id'], 'IDX_1F1B251EA76ED395')
            ->addForeignKeyConstraint(
                $schema->getTable('user'),
                ['user_id'],
                ['id'],
                ['delete' => 'CASCADE', 'update' => 'CASCADE'],
                'FK_1F1B251EA76ED395'
            )
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItem);
    }
}
