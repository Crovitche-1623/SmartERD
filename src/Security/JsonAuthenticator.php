<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\{
    AuthenticationException,
    UsernameNotFoundException
};
use Symfony\Component\Security\Core\User\{
    UserInterface,
    UserProviderInterface
};
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * This class is responsible for identifying the user from the login page only.
 * The user must send his username and plain password in JSON format to
 * the login page. If the credentials are correct, a JWT token is returned.
 * Please note, the JWT token is returned from the SecurityController.php
 */
class JsonAuthenticator extends AbstractGuardAuthenticator
{
    private UserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder
    )
    {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

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
        if ("login" === $request->attributes->get('_route') &&
            $request->isMethod('POST')) {

            // Parameters validation
            $credentials = json_decode($request->getContent(), true);

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
    public function getCredentials(Request $request): array
    {
        $credentials = json_decode($request->getContent(), true);

        return [
            'username' => $credentials['username'],
            'password' => $credentials['password']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUser(
        $credentials,
        UserProviderInterface $userProvider
    ): ?UserInterface
    {
        $username = $credentials['username'];
        $password = $credentials['password'];

        if ('' === $username && '' === $password) {
            throw new AuthenticationException(
                "Le nom d'utilisateur et le mot de passe sont nécessaires."
            );
        }

        $employee = $this->userRepository->findOneBy([
            'username' => $username
        ]);

        if (null === $employee) {
            throw new UsernameNotFoundException(
                "Aucun utilisateur avec ce nom d'utilisateur existe."
            );
        }

        return $employee;
    }

    /**
     * {@inheritDoc}
     */
    public function checkCredentials(
        $credentials,
        UserInterface $employee
    ): bool
    {
        if (!$this->passwordEncoder->isPasswordValid(
                $employee,
                $credentials['password']
            )
        ) {
            throw new AuthenticationException('Le mot de passe est incorrect.');
        }

        return true;
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
        string $providerKey
    )
    {
        // The JWT is not returned from the SecurityController.php so we don't
        // generate one here. The request continue by returning null in this
        // method.
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}
