<?php

declare(strict_types=1);

namespace App\Controller;

use App\{Entity\Project, Repository\UserRepository};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateProjectController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private SerializerInterface $serializer,
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    )
    {}

    /**
     * TODO: Replace this file by using built-in POST method in API Platform.
     *       The previous purpose of this file was to check if the project owner
     *       & the current user are the same. An additional purpose was to
     *       return application/problem+json content-type.
     */
    #[Route(
        path: '/projects',
        name: 'api_projects_post_collection',
        methods: 'POST'
    )]
    public function __invoke(Request $request): JsonResponse
    {
        // TODO: Configurer le format de retour avec un Header Accept: <format>
        //       ou plus simplement à l'aide de l'url
        //       /projects.json retournerait du JSON & /projects.xml du XML
        if ($request->headers->get('content-type') !== 'application/json') {
            throw new InvalidArgumentException(
                'The Content-Type of the request should be application/json',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Because the entity is recreated using JWT Payload, it is not the
        // "real" user yet. It will be retrieved afterwards.
        $userPayload = $this->getUser();

        if (null === $userPayload) {
            throw new AuthenticationException(
                'Authentication required',
                Response::HTTP_UNAUTHORIZED
            );
        }

        /** @var  Project  $project */
        $project = $this->serializer->deserialize(
            $request->getContent(),
            Project::class,
            'json'
        );

        $currentUser = $this->userRepository->findOneBy([
            'username' => $userPayload->getUsername()
        ]);

        $project->setUser($currentUser);

        $errors = $this->validator->validate($project);

        // TODO: All errors are returned even if the request body is
        //       empty. With Symfony 5.1, constraints can be applied
        //       sequentially, @see :
        // https://symfony.com/doc/master/reference/constraints/Sequentially.html
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST, [
                'content-type' => 'application/problem+json; charset=utf-8'
            ]);
        }

        $this->em->persist($project);
        $this->em->flush();

        return $this->json(
            $project,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'project:read']
        );
    }
}
