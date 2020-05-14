<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\Entity\{Project, User};
use App\Repository\ProjectRepository;
use App\Tests\Security\JsonAuthenticatorTest;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ProjectsTest extends ApiTestCase
{
    use FixturesTrait;

    private HttpClientInterface $client;
    private EntityManagerInterface $em;
    private bool $fixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $this->client = JsonAuthenticatorTest::login();

        if (!$this->fixturesHaveBeenLoaded) {
            $this->loadFixtures([
                // Because ProjectFixtures need UserFixtures, UserFixtures are
                // automatically loaded.
                ProjectFixtures::class
            ]);

            $this->fixturesHaveBeenLoaded = true;
        }

        parent::setUp();
    }

    public function testCreate(): void
    {
        $name = 'Test Project';

        $this->client->request(Request::METHOD_POST, '/projects', [
            'json' => ['name' => $name]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_CREATED);

        // N.B: $this->json from AbstractController return the header:
        //          "content-type: application/json"
        //      and not:
        //          "content-type: application/json; charset=utf-8"
        //      because the developers from Symfony think json should be always
        //      utf-8 encoded but api platform developers think it's more
        //      useful to have the charset for debugging and clarity.
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['name' => $name]);
    }

    /**
     * Same user could not create the same project. A Project is identified by
     * his author and his name
     */
    public function testCreateSame(): void
    {
        $sameName = 'This name is going to be inserted two times !';

        $this->client->request(Request::METHOD_POST, '/projects', [
            'json' => [
                'name' => $sameName
            ]
        ]);

        $this->client->request(Request::METHOD_POST, '/projects', [
            'json' => [
                'name' => $sameName
            ]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            'title' => 'Validation Failed',
            'detail' => "name: You have already created a project with this name \"${sameName}\""
        ]);
    }

    public function testCreateWithTooLongName(): void
    {
        $tooLongName = '';
        for ($i = 0; $i < 100; $i++) {
            $tooLongName .= 'a';
        }

        $this->client->request(Request::METHOD_POST, '/projects', [
            'json' => [
                'name' => $tooLongName
            ]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            'title' => 'Validation Failed',
            'detail' => "name: This value is too long. It should have 50 characters or less."
        ]);
    }

    public function testUserCanAccessHisProjects(): void
    {
        $this->client = JsonAuthenticatorTest::login($asAdmin = false);
        $this->client->request(Request::METHOD_GET, '/projects');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Project',
            '@id' => '/projects',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2
        ]);

        // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // This generated JSON Schema is also used in the OpenAPI spec!
        $this->assertMatchesResourceCollectionJsonSchema(Project::class);
    }

    public function testAdminCanAccessAllProjects(): void
    {
        $this->client->request(Request::METHOD_GET, '/projects');

        $projectsCountDQL = <<<DQL
            SELECT
                COUNT(p.id)
            FROM
                App\Entity\Project p
        DQL;

        /**
         * @var  integer  $projectsCount
         */
        $projectsCount = $this->em
            ->createQuery($projectsCountDQL)
            ->getSingleScalarResult();

        $lastPage = ceil((int) $projectsCount / ProjectRepository::ITEM_PER_PAGE);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Project',
            "@id" => "/projects",
            "@type" => "hydra:Collection",
            "hydra:totalItems" => intval($projectsCount),
            "hydra:view" => [
                "@id" => "/projects?page=1",
                "@type" => "hydra:PartialCollectionView",
                "hydra:first" => "/projects?page=1",
                "hydra:last" => "/projects?page=" . (int) $lastPage,
                "hydra:next" => "/projects?page=2"
            ]
        ]);
        $this->assertMatchesResourceCollectionJsonSchema(Project::class);
    }

    /**
     * @example User 1 has the projects 3,4,5 & the user 2 the projects 6,7,8:
     *          If the user 1 request /projects/6 it should return a 404
     *          response.
     */
    public function testOtherUserProjectAreNotAvailableAsUser(): void
    {
        $this->client = JsonAuthenticatorTest::login($asAdmin = false);

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

        // We are asking for a admin project so it must return a 404 response
        // not a 403 because otherwise an other user can see the id is valid.
        $this->client->request(Request::METHOD_GET, $url);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testUserProjectsAreAvailableAsAdmin(): void
    {
        /**
         * We have to retrieve the user entity to find the project iri.
         *
         * @var  User  $user
         */
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => 'user']);

        // findIriBy allows to retrieve the IRI of an item by searching for some
        // of its properties.
        $url = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME,
            'user' => $user
        ]);

        // We are asking for a admin project so it must return a 403 response
        $response = $this->client->request(Request::METHOD_GET, $url);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/contexts/Project',
            '@type' => 'https://schema.org/Project',
            'name' => ProjectFixtures::USER_PROJECT_NAME,
        ]);
        $this->assertRegExp('~^/projects/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Project::class);
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