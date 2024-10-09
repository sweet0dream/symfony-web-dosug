<?php

namespace App\Repository;

use App\Entity\Config;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Config>
 */
class ConfigRepository extends ServiceEntityRepository
{
    private const int DEFAULT_CONFIG = 1;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    public function load(): array
    {
        $config = (array)$this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', self::DEFAULT_CONFIG)
            ->getQuery()
            ->getArrayResult()[0];
        unset($config['id']);

        return $config;
    }
}
