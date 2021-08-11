<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{AuthenticationException, UserNotFoundException};
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * This class is responsible for identifying the user from the login page only.
 * The user must send his username and plain password in JSON format to
 * the login page. If the credentials are correct, a JWT token is returned.
 * Please note, the JWT token is returned from the SecurityController.php
 */
final class JsonAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private UserRepository $userRepository)
    {}

    /**
     * {@inheritDoc}
     */
    public function start(
        Request $request,
        AuthenticationException $authException = null
    ): JsonResponse
    {
        $data = [
            'message' => "Authentification par nom d'utilisateur et mot de passe nécessaire"
        ];

        return new JsonResponse($data, JsonResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request): bool
    {
        // We only ask for authentication if we're on the login page.
        // Otherwise, we ask a token for each request.
        // TODO: Check that the request "Content-Type" is "application/json"
        if ("login" === $request->attributes->get('_route') &&
            $request->isMethod('POST')) {

            // Parameters validation
            $credentials = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!empty($credentials['username']) &&
                !empty($credentials['password'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request): PassportInterface
    {
        $credentials = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $user = $this->userRepository->findOneBy([
           'username' => $credentials['username']
        ]);

        if (!$user) {
            throw new UserNotFoundException("Aucun utilisateur avec ce nom d'utilisateur existe.");
        }

        $password = $credentials['password'];

        return new Passport(
            new UserBadge($user->getUserIdentifier()),
            new PasswordCredentials($password),
            [
                new PasswordUpgradeBadge($password, $this->userRepository)
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?JsonResponse
    {
        $data = [
            'erreur' => $exception->getMessage()

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?JsonResponse
    {
        // The JWT is returned from the SecurityController.php so we don't
        // generate one here. The request continue by returning null in this
        // method.
        return null;
    }
}
