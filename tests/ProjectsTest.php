<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Project, User};
use App\Tests\Security\JsonAuthenticatorTest;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    }

    public function testCreate(): void
    {
        $title = 'Test Project';

        $this->client->request('POST', '/projects', [
            'json' => ['title' => $title]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_CREATED);

        // N.B: $this->json from AbstractController return the header:
        //      "content-type: application/json"
        //      and not:
        //      "content-type: application/json; charset=utf-8"
        //      because the developers from Symfony think json should be always
        //      utf-8 encoded but api platform developers think it's more
        //      useful to have the charset for debugging and clarity.
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains(['title' => $title]);
    }

    /**
     * Same user could not create the same project. A Project is identified by
     * his author and his title
     */
    public function testCreateSame(): void
    {
        $sameTitle = 'This title is going to be inserted two times !';

        $this->client->request('POST', '/projects', [
            'json' => ['title' => $sameTitle]
        ]);

        $this->client->request('POST', '/projects', [
            'json' => ['title' => $sameTitle]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            'title' => 'Validation Failed',
            'detail' => "title: You have already created a project with this name \"${sameTitle}\""
        ]);
    }

    public function testCreateWithTooLongTitle(): void
    {
        $tooLongTitle = '';
        for ($i = 0; $i < 100; $i++) {
            $tooLongTitle .= 'a';
        }

        $this->client->request('POST', '/projects', [
            'json' => ['title' => $tooLongTitle]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            'title' => 'Validation Failed',
            'detail' => "title: This value is too long. It should have 50 characters or less."
        ]);
    }

    public function testUserCanAccessHisProjects(): void
    {
        $this->client = JsonAuthenticatorTest::login();

        $response = $this->client->request('GET', '/projects');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['username' => 'user']);

        $this->assertJsonContains([
            '@context' => '/contexts/Project',
            '@id' => '/projects',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => ProjectFixtures::TOTAL_PROJECTS_NUMBER
        ]);

        // Because test fixtures are automatically loaded between each test, you can assert on them
        // this assert check that "item per page" is well configured for project resource.
        $this->assertCount(
            ProjectRepository::ITEM_PER_PAGE,
            $response->toArray()['hydra:member']
        );

        // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // This generated JSON Schema is also used in the OpenAPI spec!
        $this->assertMatchesResourceCollectionJsonSchema(Project::class);
    }

    public function testAdminCanAccessAllProjects(): void
    {
        $this->client = JsonAuthenticatorTest::login();

        $this->client->request('GET', '/projects');

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
        // 1. Login as an user to try to access administrator projects
        //    The following tests will be done
        //     - User can't access other user projects
        //     - Errors are correctly formatted.
        $this->client = JsonAuthenticatorTest::login(false);

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
            'title' => ProjectFixtures::ADMIN_PROJECT_NAME,
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
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testUserProjectsAreAvailableAsAdmin(): void
    {
        // 1. Login as an admin to try to access user projects
        //    The following tests will be done
        //     - admin can access other user projects
        //     - response is correctly formatted
        $this->client = JsonAuthenticatorTest::login(true);

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
            'title' => ProjectFixtures::USER_PROJECT_NAME,
            'user' => $user
        ]);

        // We are asking for a admin project so it must return a 403 response
        $response = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/contexts/Project',
            '@type' => 'https://schema.org/Project',
            'title' => ProjectFixtures::USER_PROJECT_NAME,
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