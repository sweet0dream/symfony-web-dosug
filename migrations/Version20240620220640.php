<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240620220640 extends AbstractMigration
{
    private string $tableUser = 'user';
    private const array DEFAULT_USERS = [
        'admin' => [
            'login' => 'admin',
            'password' => 'serezhahuesos'
        ],
        'test' => [
            'login' => 'test',
            'password' => 'test'
        ]
    ];

    public function getDescription(): string
    {
        return 'Added user table';
    }

    public function up(Schema $schema): void
    {
        $tableUser = $schema->createTable($this->tableUser);
        $tableUser->addColumn('id', Types::BIGINT)->setAutoincrement(true);
        $tableUser->addColumn('login', Types::STRING);
        $tableUser->addColumn('password', Types::STRING);
        $tableUser->addColumn('password_view', Types::STRING);
        $tableUser->addColumn('created_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableUser->addColumn('updated_at', Types::DATETIME_IMMUTABLE)->setDefault('CURRENT_TIMESTAMP');
        $tableUser
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['login'], 'UNIQ_8D93D649AA08CB10')
        ;
    }

    public function postUp(Schema $schema): void
    {
        foreach (self::DEFAULT_USERS as $user) {
            $this->connection->insert(
                $this->tableUser,
                [
                    'login' => $user['login'],
                    'password' => password_hash($user['password'], PASSWORD_DEFAULT),
                    'password_view' => base64_encode($user['password'])
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableUser);
    }
}
