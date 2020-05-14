<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Entity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method  Entity|null  find($id, $lockMode = null, $lockVersion = null)
 * @method  Entity|null  findOneBy(array $criteria, array $orderBy = null)
 * @method  Entity[]  findAll()
 * @method  Entity[]  findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class EntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entity::class);
    }
}