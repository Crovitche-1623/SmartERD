<?php

declare(strict_types=1);

namespace App\Tests\Functional\AsUser\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Service\JwtTokenGeneratorService;
use App\DataFixtures\{EntityFixtures, ProjectFixtures, UserFixtures};
use App\Entity\{Entity, Project, User};
use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException, NoResultException};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Contracts\HttpClient\{Exception\TransportExceptionInterface, HttpClientInterface};

final class EntitiesTest extends ApiTestCase
{
    private EntityManagerInterface $em;
    private HttpClientInterface $client;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
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
     */
    public function testCreate(): void
    {
        $projectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::USER_PROJECT_NAME_1
        ]);

        $this->client->request('POST', '/entities', [
            'json' => [
                'name' => 'testCreate',
                'project' => $projectIri
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(Entity::class);
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
     * @depends  testCreate
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

    /**
     * @throws  TransportExceptionInterface
     */
    public function testPartialUpdate(): void
    {
        $entityIri = $this->findIriBy(Entity::class, [
            'name' => EntityFixtures::USER_PROJECT_ENTITY_NAME
        ]);

        $this->client->request('PATCH', $entityIri, [
            'headers' => [
                'content-type' => 'application/merge-patch+json',
            ],
            'json' => [
                'name' => 'testPartialUpdated'
            ]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(Entity::class);
    }

    /**
     * @depends  testPartialUpdate
     */
    public function testProjectCannotChangeOverTime(): void
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

        $this->client->request('PATCH', "/entities/{$newEntity->getSlug()}", [
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
     * @depends testCreate
     */
    public function testMaxEntitiesPerProject(): void
    {
        /** @var  User  $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'username' => UserFixtures::USER_USERNAME
        ]);

        $userReference = $this->em->getReference(User::class, $user->getId());

        // 1. Create a project for testing
        $projectName = 'testMaxEntitiesPerProject';
        $projectForTesting = (new Project)
            ->setName($projectName)
            ->setUser($userReference);

        $this->em->persist($projectForTesting);
        $this->em->flush();

        $aUserProjectIri = $this->findIriBy(Project::class, [
            'name' => $projectName,
            'user' => $user->getId()
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
