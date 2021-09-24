<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\Functional\Security\JsonAuthenticatorTest;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UsersTest extends ApiTestCase
{
    private HttpClientInterface $client;
    private bool $fixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->client = JsonAuthenticatorTest::login(asAdmin: true);

        if (!$this->fixturesHaveBeenLoaded) {
            $databaseTool->loadFixtures([
                UserFixtures::class
            ]);

            $this->fixturesHaveBeenLoaded = true;
        }

        parent::setUp();
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testCreate(): void
    {
        $this->createUser();

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', "application/ld+json; charset=utf-8");
        self::assertMatchesResourceItemJsonSchema(User::class);
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testCreateAsUser(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);

        $this->createUser();

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testAdminAccessUserAccount(): void
    {
        $this->getUser();

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', "application/ld+json; charset=utf-8");
        self::assertMatchesResourceItemJsonSchema(User::class);
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testUserAccessOtherUserAccount(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);

        $this->getUser();

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testUserAccessHisAccount(): void
    {
        $this->client = JsonAuthenticatorTest::login(asAdmin: false);

        $this->getUser(UserFixtures::USER_USERNAME);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', "application/ld+json; charset=utf-8");
        self::assertMatchesResourceItemJsonSchema(User::class);
    }

    /**
     * @throws  TransportExceptionInterface
     */
    private function createUser(): void
    {
        $this->client->request('POST', '/users', [
            'json' => [
                'username' => 'Y0upl4hou',
                'email' => 'you@pla.hou',
                'plainPassword' => 'MyPasswordIsSoStrong123!'
            ]
        ]);
    }

    /**
     * @throws  TransportExceptionInterface
     */
    private function getUser(string $username = UserFixtures::ANOTHER_USER_USERNAME): void
    {
        $userIri = $this->findIriBy(User::class, [
            'username' => $username
        ]);

        $this->client->request('GET', $userIri);
    }
}
