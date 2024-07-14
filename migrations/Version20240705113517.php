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

    private const array TEST_ITEMS_PHOTO = [
        1 => [
            [
                'file_name' => '1.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719080643_showybeauty-0019.jpg'
            ], [
                'file_name' => '2.jpg',
                'has_main' => 1,
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719080666_showybeauty-0036.jpg'
            ], [
                'file_name' => '3.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719080665_showybeauty-0053.jpg'
            ], [
                'file_name' => '4.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719080645_showybeauty-0068.jpg'
            ], [
                'file_name' => '5.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719080709_showybeauty-0084.jpg'
            ]
        ],
        2 => [
            [
                'file_name' => '1.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719252384_metart_eight-ball_shea_medium_0034.jpg'
            ], [
                'file_name' => '2.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719252367_metart_eight-ball_shea_medium_0058.jpg'
            ], [
                'file_name' => '3.jpg',
                'has_main' => 1,
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719252393_metart_eight-ball_shea_medium_0065.jpg'
            ], [
                'file_name' => '4.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719252359_metart_eight-ball_shea_medium_0085.jpg'
            ], [
                'file_name' => '5.jpg',
                'link_to_copy' => 'https://ero-fox.org/uploads/posts/2024-06/thumbs/1719252330_metart_eight-ball_shea_medium_0103.jpg'
            ]
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
        foreach (self::TEST_ITEMS_PHOTO as $itemId => $itemPhotoEntity) {
            foreach($itemPhotoEntity as $itemPhoto) {
                $path = 'public/media/' . $itemId . '/src/';
                if (!file_exists($path . $itemPhoto['file_name'])) {
                    if (!is_dir($path)) {
                        mkdir($path, 0755, true);
                    }
                    copy($itemPhoto['link_to_copy'], $path . $itemPhoto['file_name']);
                }
                unset($itemPhoto['link_to_copy']);
                $this->connection->insert(
                    $this->tableItemPhoto,
                    array_merge($itemPhoto, ['item_id' => $itemId])
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable($this->tableItemPhoto);
    }
}
