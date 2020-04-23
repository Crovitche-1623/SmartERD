<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\Entity\{Project, User};
use App\Tests\Security\JsonAuthenticatorTest;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ProjectsTest extends ApiTestCase
{
    use FixturesTrait;

    private HttpClientInterface $client;
    private bool $fixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->client = JsonAuthenticatorTest::login();

        if (!$this->fixturesHaveBeenLoaded) {
            $this->loadFixtures([ProjectFixtures::class]);

            $this->fixturesHaveBeenLoaded = true;
        }
    }

    public function testCreateProject(): void
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
    public function testCreateSameProject(): void
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

    public function testCreateProjectWithTooLongTitle(): void
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

    public function testOtherUserProjectsAreNotAvailableAsUser(): void
    {
        // 1. Login as an user to try to access administrator projects
        //    The following tests will be done
        //     - User can't access other user projects
        //     - Errors are correctly formatted.
        $this->client = JsonAuthenticatorTest::login(false);

        $kernel = self::bootKernel();

        $em = $kernel
            ->getContainer()
            ->get('doctrine')
            ->getManager();


        /**
         * We have to retrieve the admin entity to find the project iri.
         *
         * @var  User  $admin
         */
        $admin = $em->getRepository(User::class)
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

        // We are asking for a admin project so it must return a 403 response
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testOtherUserProjectsAreAvailableAsAdmin(): void
    {
        // 1. Login as an admin to try to access user projects
        //    The following tests will be done
        //     - admin can access other user projects
        //     - response is correctly formatted
        $this->client = JsonAuthenticatorTest::login(true);

        $kernel = self::bootKernel();

        $em = $kernel
            ->getContainer()
            ->get('doctrine')
            ->getManager();

        /**
         * We have to retrieve the user entity to find the project iri.
         *
         * @var  User  $user
         */
        $user = $em->getRepository(User::class)
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
}