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
 * Cette classe s'occupe d'identifier l'utilisateur depuis la page de login (uniquement) via
 * son nom d'utilisateur / mot de passe.
 * Si les informations de connexions sont justes, un token JWT est retourné.
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
        // On exige l'authentification si l'on est sur la page de login
        // Dans le cas contraire, on demande à le token à chaque requête.
        if ("login" === $request->attributes->get('_route') &&
            $request->isMethod('POST')) {

            // Vérification des paramètres
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
        if (!$this->passwordEncoder->isPasswordValid($employee, $credentials['password'])) {
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
        $providerKey
    )
    {
        // Le jeton est retourné depuis le contrôleur donc on ne s'en
        // occupe pas ici. La requête poursuit en retournant null.
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
