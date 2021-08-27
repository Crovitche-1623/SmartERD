<?php

declare(strict_types = 1);

namespace App\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProjectRepository extends ServiceEntityRepository
{
    public const ITEM_PER_PAGE = 20;

    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findOneByUserAndName(
        UserInterface $user,
        string $projectName
    ): Project
    {
        return $this->_em->createQuery('
            SELECT
                p0
            FROM
                App\Entity\Project p0
                JOIN p0.user u1
            WHERE
                u1.username = :username AND
                p0.name = :projectName
        ')
        ->setParameter('username', $user->getUsername())
        ->setParameter('projectName', $projectName)
        ->getOneOrNullResult();
    }

    /**
     * @param  int  $userId
     * @param  int  $page
     *
     * @return  Paginator  @see https://api-platform.com/docs/core/pagination/#custom-controller-action
     */
    public function findByUserId(int $userId, int $page = 1): Paginator
    {
        $firstResult = ($page - 1) * self::ITEM_PER_PAGE;

        $query = $this->_em
            ->createQuery('
                SELECT
                    p1
                FROM
                    App\Entity\Project p1
                    JOIN p1.user u2
                WHERE
                    u2.id = :id
            ')
            ->setParameter('id', $userId)
            ->setFirstResult($firstResult)
            ->setMaxResults(self::ITEM_PER_PAGE);

        return new Paginator(new DoctrinePaginator($query));
    }
}
