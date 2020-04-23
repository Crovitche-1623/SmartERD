<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

/**
 * This controller name doesn't end with "Controller" because it follows the
 * ADR pattern :
 * @see https://symfony.com/doc/current/controller/service.html#invokable-controllers
 */
final class RetrieveUserProjects
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * The route details are configured in App\Entity\Project
     *
     * @param  User  $data  Warning: the parameter MUST be called $data, otherwise, it will not be filled correctly!
     *                      see: https://api-platform.com/docs/core/controllers/#creating-custom-operations-and-controllers
     */
    public function __invoke(User $data): User
    {
        $currentUser = $this->security->getUser();

        // This following condition is never true if the route is correctly
        // under a firewall
        if (null === $currentUser) {
            throw new AuthenticationException(
                'Authentication required',
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        if ($currentUser === $data || $this->security->isGranted('ROLE_ADMIN')) {
            return $data;
        } else {
            throw new AccessDeniedHttpException("You can only access your projects data unless you're an admin.");
        }
    }
}