<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\{Project, User};
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\{Event\LifecycleEventArgs, Events};
use Symfony\Component\Security\Core\Security;

final class SetProjectOwnerSubscriber implements EventSubscriber
{
    public function __construct(private Security $security)
    {}

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if (!$entity instanceof Project) {
            return;
        }

        // To prevent to change the owner, we set the owner only if the project
        // doesn't have already one. It's logic because the column is not
        // nullable and the user is null only if the project has not been
        // persisted.
        //
        // The second condition should never be true because the current user is
        // normally never null. This occurred only if the route isn't secured
        // or not behind the firewall.
        /** @var  User  $currentUser */
        $currentUser = $this->security->getUser();
        if (null !== $currentUser && null === $entity->getUser()) {
            $entity->setUser($currentUser);
        }
    }
}
