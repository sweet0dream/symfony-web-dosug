<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

final class Version20240705113517 extends AbstractMigration
{
    private string $tableItemPhoto = 'item_photo';

    private const array TEST_ITEMS_PHOTO = [
        [
            'item_id' => 1,
            'file_name' => '1.jpg'
        ], [
            'item_id' => 1,
            'file_name' => '2.jpg',
            'has_main' => 1
        ], [
            'item_id' => 1,
            'file_name' => '3.jpg'
        ], [
            'item_id' => 1,
            'file_name' => '4.jpg'
        ], [
            'item_id' => 1,
            'file_name' => '5.jpg'
        ], [
            'item_id' => 2,
            'file_name' => '1.jpg',
        ], [
            'item_id' => 2,
            'file_name' => '2.jpg'
        ], [
            'item_id' => 2,
            'file_name' => '3.jpg'
        ], [
            'item_id' => 2,
            'file_name' => '4.jpg'
        ], [
            'item_id' => 2,
            'file_name' => '5.jpg'
        ]
    ];

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

    public function postUp(Schema $schema): void
    {
        foreach (self::TEST_ITEMS_PHOTO as $itemPhoto) {
            $this->connection->insert(
                $this->tableItemPhoto,
                $itemPhoto
            );
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItemPhoto);
    }
}
