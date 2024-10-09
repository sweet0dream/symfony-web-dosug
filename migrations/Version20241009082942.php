<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241009082942 extends AbstractMigration
{
    private const array DEFAULT_CONFIG = [
        'site_name' => 'WebIntimCMS',
        'max_photo_upload' => 10,
        'cities' => 'city1,city2,city3',
    ];
    private string $tableConfig = 'config';

    public function getDescription(): string
    {
        return 'Added config table';
    }

    public function up(Schema $schema): void
    {
        $tableConfig = $schema->createTable($this->tableConfig);
        $tableConfig->addColumn('id', Types::BIGINT, ['autoincrement' => true]);
        $tableConfig->addColumn('site_name', Types::STRING, ['length' => 255])->setNotnull(true);
        $tableConfig->addColumn('max_photo_upload', Types::INTEGER)->setDefault(NULL);
        $tableConfig->addColumn('cities', Types::TEXT)->setNotnull(true);
        $tableConfig
            ->setPrimaryKey(['id'])
        ;
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->insert(
                $this->tableConfig,
            self::DEFAULT_CONFIG
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableConfig);
    }
}
