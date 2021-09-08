<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\{EntityFixtures, ProjectFixtures, UserFixtures};
use App\Entity\{Entity, Project};
use App\Tests\Security\JsonAuthenticatorTest;
use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException, NoResultException};
use Faker\{Factory, Generator};
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);
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

    public function testCreateEntity(
        string $entityName = 'test',
        bool $asAdmin = false
    ): ResponseInterface
    {
        $this->client = JsonAuthenticatorTest::login($asAdmin);

        $projectIri = $this->findIriBy(Project::class, [
            'name' => $asAdmin ?
                ProjectFixtures::ADMIN_PROJECT_NAME :
                ProjectFixtures::USER_PROJECT_NAME_1
        ]);

        $response = $this->client->request('POST', '/entities', [
            'json' => [
                'name' => $entityName,
                'project' => $projectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(Entity::class);

        return $response;
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

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "Not Found"
        ]);
    }

    /**
     * It's useless to test this case if we can't create a simple entity
     *
     * @depends  testCreateEntity
     */
    public function testCreateAnEntityInAnotherUserProjectReturn404(): void
    {
        $adminProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::ADMIN_PROJECT_NAME
        ]);

        $this->client->request('POST', '/entities', [
            'json' => [
                'name' => 'LetMeInsertMySelfHere',
                'project' => $adminProjectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "Item not found for \"${adminProjectIri}\"."
        ]);
    }

    // TODO: Check if the project of entity cannot be changed over time.
    //       (check serialization_group for PATCH)

    public function testPartialUpdateEntity(): void
    {
        /** @var  Project  $projectFromFixtures */
        $projectFromFixtures = $this->em->getRepository(Project::class)->findOneBy([
            'name' => ProjectFixtures::USER_PROJECT_NAME_1
        ]);

        $projectFromFixtures = $this->em->getReference(Project::class, $projectFromFixtures->getId());

        $aNewEntity = (new Entity)
            ->setName('test')
            ->setProject($projectFromFixtures)
        ;

        $this->em->persist($aNewEntity);
        $this->em->flush();

        $this->client->request('PATCH', "/entities/{$aNewEntity->getId()}", [
            'headers' => [
                'content-type' => 'application/merge-patch+json',
            ],
            'json' => [
                'name' => 'anotherName'
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(Entity::class);
    }

    /**
     * @depends  testPartialUpdateEntity
     */
    public function testEntityProjectCannotChangeOverTime(): void
    {
        /** @var  Project  $project */
        $project = $this->em->getRepository(Project::class)->findOneBy([
            'name' => ProjectFixtures::USER_PROJECT_NAME_1
        ]);

        $project = $this->em->getReference(Project::class, $project->getId());

        $newEntity = (new Entity)
            ->setName('MyProjectCannotChangeOverTime')
            ->setProject($project);

        unset($project);

        $this->em->persist($newEntity);
        $this->em->flush();

        $anotherUserProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME_2
        ]);

        $this->client->request('PATCH', "/entities/{$newEntity->getId()}", [
            'headers' => [
              'content-type' => 'application/merge-patch+json',
            ],
            'json' => [
                'project' => $anotherUserProjectIri,
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(Entity::class);
    }

    /**
     * @depends testCreateEntity
     */
    public function testMaxEntitiesPerProject(): void
    {
        $this->purgeUserProject();

        $aUserProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME_1
        ]);

        for ($i = 0; $i < Project::MAX_ENTITIES_PER_PROJECT; ++$i) {
            // Use a formatter to convert the index (for example: 1) to number
            // written using letters (to one)
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $name = str_replace('-', '', $formatter->format($i));
            $this->client->request('POST', '/entities', [
                'json' => [
                    'name' => $name,
                    'project' => $aUserProjectIri
                ]
            ]);
        }

         $this->client->request('POST', '/entities', [
            'json' => [
                'name' => 'IAmTryingToExceedTheLimit',
                'project' => $aUserProjectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "project: The maximum number of Entity (". Project::MAX_ENTITIES_PER_PROJECT .") for this Project has been reached."
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
                ->setParameter('projectName', ProjectFixtures::USER_PROJECT_NAME_1, 'string')
                ->getSingleScalarResult();
        } catch (NoResultException) {
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

    public function testNameWithNotOnlyAlphabeticalCharacter(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: true);

        $adminProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::ADMIN_PROJECT_NAME
        ]);

        $this->client->request('POST', '/entities', [
            'json' => [
                /** Entité contains é which is an accentued character */
                'name' => 'Entité',
                'project' => $adminProjectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
        self::assertJsonContains([
            'title' => 'An error occurred',
            'detail' => "name: This value is not valid."
        ]);
    }

    public function testDeleteAnEntity(): void
    {
        $userProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME_1
        ]);

        // TODO: Delete a entity specified in fixture instead of creating a new
        //       one here. This can ensure we can delete entities even if
        //       creating new entities does not work using the api
        $response = $this->client->request('POST', '/entities', [
            'json' => [
                'name' => 'Entity',
                'project' => $userProjectIri
            ]
        ]);

        $iri = $response->toArray()['@id'];

        $this->client->request('DELETE', $iri);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteEntityInAnotherUserProjectReturn404(): void
    {
        // We're logged as user by default and we will try to delete an entity
        // in a admin project.

        $adminEntityIri = $this->findIriBy(Entity::class, [
            'name' => EntityFixtures::ADMIN_PROJECT_ENTITY_NAME
        ]);

        $this->client->request('DELETE', $adminEntityIri);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
