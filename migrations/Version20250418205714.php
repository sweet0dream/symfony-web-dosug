<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20250418205714 extends AbstractMigration
{
    private string $tableItem = 'item';
    public function getDescription(): string
    {
        return 'Added rao column to item table';
    }

    public function up(Schema $schema): void
    {
        $schema
            ->getTable($this->tableItem)
            ->addColumn('rao', Types::SMALLINT)
            ->setDefault(0)
            ->setNotnull(true)
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable($this->tableItem)->dropColumn('rao');
    }
}
