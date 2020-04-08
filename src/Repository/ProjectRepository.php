<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method  Project|null  find($id, $lockMode = null, $lockVersion = null)
 * @method  Project|null  findOneBy(array $criteria, array $orderBy = null)
 * @method  Project[]  findAll()
 * @method  Project[]  findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findByTitleAndUser(array $criteria): int
    {
        $request = $this->_em->createQuery('
            SELECT
                COUNT(project.id)
            FROM 
                App\Entity\Project project
            WHERE
                project.title = :title AND
                project.user = :user
        ');
        die('test !');

        $request->setParameter('title', $criteria['title']);
        $request->setParameter('user', $criteria['user'], UserInterface::class);

        dd($request->execute(null, AbstractQuery::HYDRATE_SINGLE_SCALAR));

        return $request->execute(null, AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }
}
