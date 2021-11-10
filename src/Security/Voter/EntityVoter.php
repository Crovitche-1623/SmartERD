<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\{Entity, User};
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

final class EntityVoter extends Voter
{
    public function __construct(private Security $security)
    {}

    /**
     * {@inheritDoc}
     */
    #[Pure]
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, ['ENTITY_VIEW'])
            && $subject instanceof Entity;
    }

    /**
     * {@inheritDoc}
     *
     * @param  string  $attribute
     * @param  Entity  $subject
     * @param  TokenInterface  $token
     *
     * @return  bool
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        // Explication : When the user is not logged, the "getUser()" method
        //               return null. Null is not an instanceof UserInterface...
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case 'ENTITY_VIEW':
                return $this->canView($subject, $user);
        }

        return false;
    }

    /**
     * @throws  \Exception
     */
    private function canView(Entity $entity, User $currentUser): bool
    {
        // User can view the entity only if the entity belongs to a project he
        // own.
        $userProject = $entity->getProject();

        if (!$userProject) {
            throw new \Exception('Entity project is missing. Does the fetch eager is enabled ?');
        }

        /**
         * @var  User  $user
         */
        $user = $userProject->getUser();

        if ($user->getSlug() === $currentUser->getSlug()) {
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
