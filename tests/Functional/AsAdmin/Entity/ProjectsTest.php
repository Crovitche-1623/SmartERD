<?php

declare(strict_types=1);

namespace App\Tests\Functional\AsAdmin\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Service\JwtTokenGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ProjectsTest extends ApiTestCase
{
    private HttpClientInterface $client;
    private EntityManagerInterface $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();

        $this->client = self::createClient(defaultOptions: [
            'auth_bearer' => $container->get(JwtTokenGeneratorService::class)(asAdmin: true)
        ]);

        parent::setUp();
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testCreate(): void
    {
        $name = 'testCreate';

        $this->client->request('POST', '/projects', [
            'json' => ['name' => $name]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', "application/ld+json; charset=utf-8");
        self::assertJsonContains(['name' => $name]);
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testCreateProjectWithBlankName(): void
    {
        $this->client->request('POST', '/projects', [
            'json' => ['name' => ""]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "name: This value should not be blank."
        ]);
    }

    /**
     * Same user could not create the same project. A Project is identified by
     * his author and his name
     *
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testCreateSame(): void
    {
        $sameName = 'testCreateSame';

        $this->client->request('POST', '/projects', [
            'json' => ['name' => $sameName]
        ]);

        $this->client->request('POST', '/projects', [
            'json' => ['name' => $sameName]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "name: You have already created a project with this name \"${sameName}\""
        ]);
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testCreateWithTooLongName(): void
    {
        $this->client->request('POST', '/projects', [
            'json' => [
                'name' => str_repeat('a', 100)
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "name: This value is too long. It should have 50 characters or less."
        ]);
    }

    /**
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  NonUniqueResultException
     * @throws  NoResultException
     */
    public function testCanAccessAllProjects(): void
    {
        $this->client->request('GET', '/projects');

        $projectsCountDQL = <<<DQL
            SELECT
                COUNT(p.id)
            FROM
                App\Entity\Project p
        DQL;

        $projectsCount = (int) $this->em
            ->createQuery($projectsCountDQL)
            ->getSingleScalarResult();

        $lastPage = (int) ceil($projectsCount / ProjectRepository::ITEM_PER_PAGE);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            '@context' => '/contexts/Project',
            "@id" => "/projects",
            "@type" => "hydra:Collection",
            "hydra:totalItems" => $projectsCount,
            "hydra:view" => [
                "@id" => "/projects?page=1",
                "@type" => "hydra:PartialCollectionView",
                "hydra:first" => "/projects?page=1",
                "hydra:last" => "/projects?page=" . $lastPage,
                "hydra:next" => "/projects?page=2"
            ]
        ]);
        self::assertMatchesResourceCollectionJsonSchema(Project::class);
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testUserProjectsAreAvailable(): void
    {
        /**
         * We have to retrieve the user entity to find the project iri.
         *
         * @var  User  $user
         */
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => UserFixtures::USER_USERNAME]);

        // findIriBy allows to retrieve the IRI of an item by searching for some
        // of its properties.
        $url = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME_1,
            'user' => $user
        ]);

        // We are asking for an admin project, so it must return a 403 response
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        // Asserts that the returned JSON is a superset of this one
        self::assertJsonContains([
            '@context' => '/contexts/Project',
            '@type' => 'https://schema.org/Project',
            'name' => ProjectFixtures::USER_PROJECT_NAME_1,
        ]);
        self::assertMatchesResourceItemJsonSchema(Project::class);
    }
}
