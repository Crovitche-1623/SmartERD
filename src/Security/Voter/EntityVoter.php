<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Entity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class EntityVoter extends Voter
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritDoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, ['ENTITY_VIEW'])
            && $subject instanceof Entity;
    }

    /**
     * {@inheritDoc}
     *
     * @param  Entity  $subject
     */
    protected function voteOnAttribute(
        $attribute,
        $subject,
        TokenInterface $token
    ): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        // Explication : When the user is not logged, the "getUser()" method
        //               return null. Null is not an instanceof UserInterface...
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case 'ENTITY_VIEW':
                return $this->canView($subject, $user);
                break;
        }

        return false;
    }

    private function canView(Entity $entity, UserInterface $currentUser): bool
    {
        // User can view the entity only if the entity belongs to a project he
        // own.
        if ($entity->getProject()->getUser() === $currentUser) {
            return true;
        }

        // Admin have a global view for all projects, so the entities should be
        // available too.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return false;
    }
}
