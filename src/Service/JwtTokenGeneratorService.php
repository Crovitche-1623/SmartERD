<?php

declare(strict_types=1);

namespace App\Service;

use App\DataFixtures\UserFixtures as Fixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * This service is intended to be used in tests to speed up login process.
 */
final class JwtTokenGeneratorService
{
    public function __construct(
        private JWTTokenManagerInterface $tokenManager,
        private UserRepository $userRepository
    )
    {}

    public function __invoke(
        bool $asAdmin = false
    ): string
    {
        // Slug cannot be created on the fly because it'll be different from
        // the one created in fixtures, so we must get the real user in
        // database...But it's still faster than doing a real login with
        // a POST request.

        /** @var  User  $user */
        $user = $this->userRepository->findOneBy([
            'username' => $asAdmin ?
                Fixtures::ADMIN_USERNAME :
                Fixtures::USER_USERNAME
        ]);

        return $this->tokenManager->createFromPayload(
            $user,
            ['sub' => $user->getSlug()]
        );
    }
}
