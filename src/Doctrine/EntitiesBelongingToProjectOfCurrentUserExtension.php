<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\{Entity, User};
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

/**
 * When the user do a /entities/{id}, a where condition should be applied to the
 * query to avoid 403 response. A user should not be aware the entity he wants
 * belongs to another project.
 * This also applies when creating a new Entity using POST /entities. If the
 * project iri (passed in request body) belong to another user, user won't know
 * because a where condition is applied in search query.
 */
final class EntitiesBelongingToProjectOfCurrentUserExtension implements
    QueryItemExtensionInterface
{
    public function __construct(private Security $security)
    {}

    /*
     * This method check if the resource class parameter does not match the
     * Entity class then it checks if the current user is an administrator or
     * if the user is not logged.
     * If one of theses above conditions is true, the rest of the method is not
     * executed.
     * Then it adds a where condition to the query to have only the projects of
     * current user.
     */
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    ): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(
        QueryBuilder $queryBuilder,
        string $resourceClass
    ): void
    {
        if (Entity::class !== $resourceClass ||
            $this->security->isGranted('ROLE_ADMIN') ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        /** @var  User  $user */
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->innerJoin(sprintf('%s.project', $rootAlias), 'p')
            ->andWhere('p.user = :current_user')
            ->setParameter('current_user', $user->getId())
        ;
    }
}
