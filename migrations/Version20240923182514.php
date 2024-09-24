<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240923182514 extends AbstractMigration
{
    private string $tableAdvert = 'advert';

    public function getDescription(): string
    {
        return 'Added advert table';
    }

    public function up(Schema $schema): void
    {
        $tableEvent = $schema->createTable($this->tableAdvert);
        $tableEvent->addColumn('id', Types::BIGINT)->setAutoIncrement(true);
        $tableEvent->addColumn('filename', Types::STRING)->setNotnull(true);
        $tableEvent->addColumn('section', Types::TEXT)->setDefault(null)->setNotnull(false);
        $tableEvent->addColumn('created_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableEvent->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableAdvert);
    }
}
