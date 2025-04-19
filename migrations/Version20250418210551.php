<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20250418210551 extends AbstractMigration
{
    private string $tableConfig = 'config';

    private const array DEFAULT_DISTRICTS_CITY = [
        'Только выезд'
    ];

    public function getDescription(): string
    {
        return 'Added districtsCity column to config table';
    }

    public function up(Schema $schema): void
    {
        $schema
            ->getTable($this->tableConfig)
            ->addColumn('districts_city', Types::JSON)
            ->setNotnull(true)
        ;
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->update(
            table: $this->tableConfig,
            data: [
                'districts_city' => json_encode(self::DEFAULT_DISTRICTS_CITY, JSON_UNESCAPED_UNICODE)
            ],
            criteria: ['id' => 1],
        );
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->tableConfig)->dropColumn('districts_city');
    }
}
