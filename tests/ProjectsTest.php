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
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
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
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
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
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertJsonContains([
            'title' => 'Validation Failed',
            'detail' => "title: This value is too long. It should have 50 characters or less."
        ]);
    }

    public function testProjectOnlyAvailableForOwnerOrAdmin(): void
    {
        // Login as user
        $this->client = JsonAuthenticatorTest::login(false);

        $kernel = self::bootKernel();

        $em = $kernel
            ->getContainer()
            ->get('doctrine')
            ->getManager();

        /**
         * @var  User  $admin
         */
        $admin = $em->getRepository(User::class)
            ->findOneBy(['username' => 'admin']);

        // findIriBy allows to retrieve the IRI of an item by searching for some
        // of its properties.
        $url = $this->findIriBy(Project::class, [
            'title' => 'A simple project for testing purpose',
            'user' => $admin
        ]);


        // TODO: Disable logging PHP Exception in terminal when it's testing
        //       environment

        // We are asking for a admin project so it must return a 403
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_FORBIDDEN);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }
}