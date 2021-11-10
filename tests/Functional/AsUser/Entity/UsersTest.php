<?php

declare(strict_types=1);

namespace App\Tests\Functional\AsUser\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Service\JwtTokenGeneratorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UsersTest extends ApiTestCase
{
    private HttpClientInterface $client;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $container = self::getContainer();

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
        $this->createUser();

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testAccessOtherUserAccount(): void
    {
        $this->getUser();

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function testAccessCurrentAccount(): void
    {
        $this->getUser(UserFixtures::USER_USERNAME);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', "application/ld+json; charset=utf-8");
        self::assertMatchesResourceItemJsonSchema(User::class);
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
}
