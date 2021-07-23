<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Entity;
use App\DataFixtures\{EntityFixtures, ProjectFixtures, UserFixtures};
use App\Entity\Project;
use App\Tests\Security\JsonAuthenticatorTest;
use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException, NoResultException};
use Faker\{Factory, Generator};
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EntitiesTest extends ApiTestCase
{
    private EntityManagerInterface $em;
    private HttpClientInterface $client;
    private Generator $faker;
    private bool $fixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $databaseTool = $kernel->getContainer()->get(DatabaseToolCollection::class)->get();
        $this->client = JsonAuthenticatorTest::login($asAdmin = false);
        $this->faker = Factory::create('fr_CH');
        if (!$this->fixturesHaveBeenLoaded) {
            $databaseTool->loadFixtures([
                // Because ProjectFixtures need UserFixtures, UserFixtures are
                // automatically loaded.
                EntityFixtures::class
            ]);

            $this->fixturesHaveBeenLoaded = true;
        }
        parent::setUp();
    }

    public function testUserCantAccessEntitiesFromProjectHeDoesNotOwn(): void
    {
        $adminEntityId = $this->em
            ->createQuery(<<<DQL
                SELECT
                    e0.id
                FROM
                    App\Entity\Entity e0
                    JOIN e0.project p1
                WHERE
                    p1.name = :projectName AND
                    e0.name = :entityName
            DQL)
            ->setParameter('projectName', ProjectFixtures::ADMIN_PROJECT_NAME)
            ->setParameter('entityName', EntityFixtures::ADMIN_PROJECT_ENTITY_NAME)
            // ->getSql(); dd($adminEntityId);
            ->getSingleScalarResult();

        $adminEntityIri = $this->findIriBy(Entity::class, [
            'id' => $adminEntityId
        ]);
        unset($adminEntityId);

        $this->client->request(Request::METHOD_GET, $adminEntityIri);

        self::assertResponseStatusCodeSame(JsonResponse::HTTP_NOT_FOUND);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "Not Found"
        ]);
    }

    public function testCreateAnEntityInAnotherUserProjectReturn404(): void
    {
        $adminProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::ADMIN_PROJECT_NAME
        ]);

        $this->client->request(Request::METHOD_POST, '/entities', [
            'json' => [
                'name' => 'LetMeInsertMySelfHere',
                'project' => $adminProjectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(JsonResponse::HTTP_BAD_REQUEST);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "Item not found for \"${adminProjectIri}\"."
        ]);
    }

    // TODO: Check if the project of entity cannot be changed over time.
    //       (check serialization_group for PATCH)

    public function testMaxEntitiesPerProject(): void
    {
        $this->client = JsonAuthenticatorTest::login($asAdmin = false);

        $this->purgeUserProject();

        $aUserProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME
        ]);

        for ($i = 0; $i < 30; ++$i) {
            $this->client->request(Request::METHOD_POST, '/entities', [
                'json' => [
                    'name' => $this->faker->unique()->text(50),
                    'project' => $aUserProjectIri
                ]
            ]);
        }

        $this->client->request(Request::METHOD_POST, '/entities', [
            'json' => [
                'name' => 'IAmTryingToExceedTheLimit',
                'project' => $aUserProjectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "project: The maximum number of entities per project is 30."
        ]);

        // Revert the database to original state for the other tests.
        $this->fixturesHaveBeenLoaded = false;
    }

    /**
     *
     * @return  bool  Return false if the project doesn't exist
     * @throws  NonUniqueResultException  If the database structure has changed
     *                                    and many result are returned with the
     *                                    given parameters.
     */
    private function purgeUserProject(): bool
    {
        try {
            $projectId = $this->em
                ->createQuery(<<<DQL
                    SELECT
                        p0.id
                    FROM
                        App\Entity\Project p0
                        JOIN p0.user u1
                    WHERE
                        u1.username = :username AND
                        p0.name = :projectName
                DQL)
                ->setParameter('username', UserFixtures::USER_USERNAME, 'string')
                ->setParameter('projectName', ProjectFixtures::USER_PROJECT_NAME, 'string')
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return false;
        }

        $this->em
            ->createQuery(<<<DQL
                 DELETE
                    App\Entity\Entity e0
                 WHERE
                    e0.project = :projectId
             DQL)
            ->setParameter('projectId', $projectId, 'integer')
            ->execute();

        return true;
    }
}
