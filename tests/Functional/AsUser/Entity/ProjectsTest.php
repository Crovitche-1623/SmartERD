<?php

declare(strict_types=1);

namespace App\Tests\Functional\AsUser\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\DataFixtures\UserFixtures;
use App\Service\JwtTokenGeneratorService;
use App\Entity\{Project, User};
use Doctrine\ORM\EntityManagerInterface;
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
            'auth_bearer' => $container->get(JwtTokenGeneratorService::class)(asAdmin: false)
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
    public function testCreateToMuch(): void
    {
        // 1. Check remaining projects we have to create to reach the limit
        $currentProjectCount = $this->client->request('GET', '/projects')->toArray()['hydra:totalItems'];
        $projectsCountToCreate = User::MAX_PROJECTS_PER_USER - $currentProjectCount;
        $user = $this->em->getRepository(User::class)->findOneBy([
            'username' => UserFixtures::USER_USERNAME
        ]);

        $userReference = $this->em->getReference(User::class, $user->getId());

        // 2. Create the maximum projects allowed
        for ($i = 0; $i < $projectsCountToCreate; $i++) {
            $this->em->persist(
                (new Project)
                    ->setName('testCreateToMuch'. $i)
                    ->setUser($userReference)
            );
        }
        $this->em->flush();

        // 3. Try to exceed the limit
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
     * @throws  TransportExceptionInterface
     * @example  User 1 has the projects 3,4,5 & the user 2 the projects 6,7,8:
     *           If the user 1 request /projects/6 it should return a 404
     *           response.
     */
    public function testOtherUserProjectAreNotAvailable(): void
    {
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
