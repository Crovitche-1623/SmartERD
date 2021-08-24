<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\Events;

/**
 * This subscriber is responsible for hashing the password in the case where
 * user->getPlainPassword() isn't null. After the password has been hashed, the
 * method $user->eraseCredentials() is called and the plainPassword is removed.
 */
final class HashUserPasswordSubscriber implements EventSubscriber
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    )
    {}

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if (!$entity instanceof User) {
            return;
        }

        $this->encodePassword($entity);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if (!$entity instanceof User) {
            return;
        }

        $this->encodePassword($entity);

        // necessary to force the update to see the change
        $em = $args->getEntityManager();
        $meta = $em->getClassMetadata(get_class($entity));
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $entity);
    }

    private function encodePassword(User $user): void
    {
        if (null !== $user->getPlainPassword()) {
            $user->setHashedPassword($this->passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            ));
            $user->eraseCredentials();
        }
    }
}
