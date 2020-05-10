<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\Project;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\{
    QueryCollectionExtensionInterface,
    QueryItemExtensionInterface
};

/**
 * @see https://api-platform.com/docs/core/extensions/#custom-doctrine-orm-extension
 *
 * Always get the projects owned by the current user. Throw an exception if
 * current user is not the owner unless the user has the ROLE_ADMIN.
 */
final class CurrentUserExtension implements
    QueryCollectionExtensionInterface,
    QueryItemExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Modify the DQL Query (used to create the collection) by adding a where
     * condition to check if the owner is the current user or has the
     * "ROLE_ADMIN" role.
     *
     * @param  QueryBuilder  $queryBuilder
     * @param  QueryNameGeneratorInterface  $queryNameGenerator
     * @param  string  $resourceClass
     * @param  string|null  $operationName
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    /**
     * Modify the DQL Query (used to create the item) by adding a where
     * condition to check if the owner is the current user or has the
     * "ROLE_ADMIN" role.
     *
     * @param  QueryBuilder  $queryBuilder
     * @param  QueryNameGeneratorInterface  $queryNameGenerator
     * @param  string  $resourceClass
     * @param  array  $identifiers
     * @param  string|null  $operationName
     * @param  array  $context
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
        if (
            Project::class !== $resourceClass
            ||
            $this->security->isGranted('ROLE_ADMIN')
            ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
        $queryBuilder->setParameter('current_user', $user->getId());
    }
}