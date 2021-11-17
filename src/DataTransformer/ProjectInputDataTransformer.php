<?php

declare(strict_types=1);

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ProjectInput;
use App\Repository\UserRepository;
use App\Entity\{Project, User};
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

final class ProjectInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository
    )
    {}

    /**
     * {@inheritDoc}
     *
     * @param  ProjectInput  $object
     * @param  array<string, bool|string|array<array-key, string>|null|string>  $context
     */
    public function transform($object, string $to, array $context = []): Project
    {
        // It's the user payload from the jwt. The user is not fully hydrated
        // yet.
        /** @var  User|null  $userPayload */
        $userPayload = $this->security->getUser();

        // This exception should not occur if the route is correctly configured
        // or under a firewall
        if (!$userPayload) {
            throw new AuthenticationException(
                'Authentication required',
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Check if the user still exist in database and get the real object to
        // persist the object correctly
        $currentUser = $this->userRepository->findOneBy([
            'username' => $userPayload->getUsername()
        ]);

        return (new Project)
            ->setName($object->name)
            ->setUser($currentUser)
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @param  object  $data  /!\ Data can be an array too but PHPStan is angry
     *                            if the array is not typed.
     * @param  array<string, array<int|string, string>|string|bool|null>  $context
     */
    public function supportsTransformation(
        $data,
        string $to,
        array $context = []
    ): bool
    {
        // if it's a project we transformed the data already
        if ($data instanceof Project) {
            return false;
        }

        // Check if the project is the desired target and if the input is
        // configured
        /** @var array<string, string|null>  $input */
        $input = $context['input'];
        $inputClass = $input['class'];
        return Project::class === $to &&
               null !== ($inputClass ?? null);
    }
}
