<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\{OptimisticLockException,ORMException};
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\{PasswordUpgraderInterface, UserInterface};

/**
 * @template-extends  ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository implements
    PasswordUpgraderInterface
{
    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @throws  ORMException
     * @throws  OptimisticLockException
     */
    public function upgradePassword(
        UserInterface $user,
        string $newHashedPassword
    ): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    get_class($user)
                )
            );
        }
        $user->setHashedPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }
}
