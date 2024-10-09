<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class Version20240705113517 extends AbstractMigration
{
    private string $tableItemPhoto = 'item_photo';

    public function getDescription(): string
    {
        return 'Added item_photo table';
    }

    public function up(Schema $schema): void
    {
        $tableItemPhoto = $schema->createTable($this->tableItemPhoto);
        $tableItemPhoto->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableItemPhoto->addColumn('item_id', Types::BIGINT)->setDefault(null);
        $tableItemPhoto->addColumn('file_name', Types::STRING)->setNotnull(true);
        $tableItemPhoto->addColumn('has_main', Types::BOOLEAN)->setDefault(false);
        $tableItemPhoto->addColumn('created_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItemPhoto
            ->setPrimaryKey(['id'])
            ->addIndex(['item_id'], 'IDX_3E109FC8126F525E')
            ->addForeignKeyConstraint(
                $schema->getTable('item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => 'CASCADE'],
                'FK_3E109FC8126F525E'
            )
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItemPhoto);
    }
}
