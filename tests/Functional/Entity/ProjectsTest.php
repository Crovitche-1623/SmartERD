<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\DataFixtures\UserFixtures;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use App\Entity\{Project, User};
use App\Repository\ProjectRepository;
use App\Tests\Functional\Security\JsonAuthenticatorTest;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
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
    private bool $fixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->client = JsonAuthenticatorTest::login(asAdmin: true);

        if (!$this->fixturesHaveBeenLoaded) {
            $databaseTool->loadFixtures([
                // Because ProjectFixtures need UserFixtures, UserFixtures are
                // automatically loaded.
                ProjectFixtures::class
            ]);

            $this->fixturesHaveBeenLoaded = true;
        }

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
        $name = 'Test Project';

        $this->client->request('POST', '/projects', [
            'json' => ['name' => $name]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // N.B: $this->json from AbstractController return the header:
        //          "content-type: application/json"
        //      and not:
        //          "content-type: application/json; charset=utf-8"
        //      because the developers from Symfony think json should be always
        //      utf-8 encoded but api platform developers think it's more
        //      useful to have the charset for debugging and clarity.
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
        $sameName = 'This name is going to be inserted two times !';

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
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  ORMException
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testCreateToMuchProject(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);
        // First, we have to delete all the projects belonging to the user
        // To do this, we'll use the user from DataFixtures, so we don't have to
        // create a new one.

        // 1. Retrieve the user using DataFixture constants.
        $user = $this->em->getRepository(User::class)->findOneBy([
           'username' => UserFixtures::USER_USERNAME
        ]);

        $user = $this->em->getReference(User::class, $user->getId());

        // 2. Use the user id to delete all the projects
        $this->em->createQuery(<<<DQL
                DELETE
                    App\Entity\Project p0
                WHERE
                    p0.user = :userId
            DQL)
            ->setParameter('userId', $user->getId())
            ->execute();

        // 3. Create all the project using the repository, so we're not
        //    dependent on the route. To explain furthermore, the error message
        //    should always work even if the data cannot be persisted for
        //    instance
        for ($i = 0; $i < User::MAX_PROJECTS_PER_USER; ++$i) {
            $this->em->persist(
                (new Project)
                    ->setName((string) $i)
                    ->setUser($user)
            );
        }
        $this->em->flush();

         $this->client->request('POST', '/projects', [
            'json' => [
                'name' => 'ThisProjectExceedTheLimit'
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "user: The maximum number of Project (". User::MAX_PROJECTS_PER_USER .") for this User has been reached."
        ]);

        // Revert the database to original state for the other tests.
        $this->fixturesHaveBeenLoaded = false;
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testUserCanAccessHisProjects(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);
        $this->client->request('GET', '/projects');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            '@context' => '/contexts/Project',
            '@id' => '/projects',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2
        ]);

        // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // This generated JSON Schema is also used in the OpenAPI spec!
        // FIXME: @see https://github.com/api-platform/core/issues/4433
        // self::assertMatchesResourceCollectionJsonSchema(Project::class);
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
    public function testAdminCanAccessAllProjects(): void
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
     * @example  User 1 has the projects 3,4,5 & the user 2 the projects 6,7,8:
     *           If the user 1 request /projects/6 it should return a 404
     *           response.
     */
    public function testOtherUserProjectAreNotAvailableAsUser(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);

        /**
         * We have to retrieve the admin entity to find the project iri.
         *
         * @var  User  $admin
         */
        $admin = $this->em->getRepository(User::class)
            ->findOneBy(['username' => 'admin']);

        // findIriBy allows to retrieve the IRI of an item by searching for some
        // of its properties.
        $url = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::ADMIN_PROJECT_NAME,
            'user' => $admin
        ]);


        // The following line generate a warning in log and it's totally
        // normal because this endpoint is intended to be called by project
        // owner or admin only.
        // Make sure you have installed the log component :
        //  - "composer require logger" if you're using symfony flex,
        //  - "composer require symfony/monolog-bundle" otherwise
        // otherwise log will appear directly in terminal.

        // We are asking for an admin project, so it must return a 404 response
        // not a 403 because otherwise another user can see the id is valid.
        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testUserProjectsAreAvailableAsAdmin(): void
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

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->em->close();
        unset($this->em);
    }
}