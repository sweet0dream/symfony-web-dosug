<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240621114620 extends AbstractMigration
{

    private string $tableItem = 'item';
    private const array TEST_ITEMS = [
        [
            'phone' => '+7(987)654-32-10',
            'name' => 'Алина',
            'type' => 'ind',
            'info' => [
                'year' => 2,
                'height' => 20,
                'weight' => 20,
                'chest' => 2,
                'hair' => 3
            ],
            'service' => [
                'sex' => ['sk', 'sa'],
                'min' => ['mp', 'mg']
            ],
            'photo' => '1,2,3',
            'price' => [
                'express' => 1500,
                'onehour' => 2500,
                'twohour' => 4500,
                'night' => 15000
            ]
        ], [
            'phone' => '+7(987)654-32-11',
            'name' => 'Михаил',
            'type' => 'man',
            'info' => [
                'year' => 4,
                'height' => 25,
                'weight' => 25,
                'mbr' => 4,
                'body' => 2
            ],
            'service' => [
                'sex' => ['sk', 'sa'],
                'min' => ['mp', 'mg']
            ],
            'photo' => '4,5,6',
            'price' => [
                'express' => 1000,
                'onehour' => 1500,
                'twohour' => 3000,
                'night' => 10000
            ]
        ]
    ];

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
        $tableItem->addColumn('photo', Types::TEXT)->setDefault(null);
        $tableItem->addColumn('price', Types::TEXT)->setNotnull(true);
        $tableItem->addColumn('created_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItem->addColumn('updated_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItem->addColumn('toped_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableItem
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['phone'], 'UNIQ_1F1B251E444F97DD')
            ->addIndex(['user_id'], 'IDX_1F1B251EA76ED395')
        ;
    }

    public function postUp(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        foreach (self::TEST_ITEMS as $item) {
            $this->connection->insert(
                $this->tableItem,
                array_merge(
                    ['user_id' => 2],
                    array_map(fn($value) => is_array($value) ? json_encode($value) : $value, $item)
                )
            );
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItem);
    }
}
