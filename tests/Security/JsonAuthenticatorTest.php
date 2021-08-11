<?php

declare(strict_types=1);

namespace App\Tests\Security;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Make sure you have created the database before running the tests.
 */
final class JsonAuthenticatorTest extends ApiTestCase
{
    private bool $userFixturesHaveBeenLoaded = false;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $databaseTool = $container->get(DatabaseToolCollection::class)->get();

        if (!$this->userFixturesHaveBeenLoaded) {
            $databaseTool->loadFixtures([UserFixtures::class]);
            $this->userFixturesHaveBeenLoaded = true;
        }

        parent::setUp();
    }

    public static function login(bool $asAdmin = true): HttpClientInterface
    {
        $client = self::createClient(
            defaultOptions: ['base_uri' => 'http://localhost:8080/']
        );

        $response = $client->request('POST', '/login_check', [
            'json' => [
                'username' => $asAdmin ? 'admin' : 'user',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        return self::createClient([], [
            'base_uri' => 'http://localhost:8080/',
            'auth_bearer' => $response->toArray()['token'],
        ]);
    }

    public function testPrivatePageIsInaccessible(): void
    {
        self::createClient()->request('GET', '/');

        self::assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
        self::assertResponseHasHeader('Content-Type', 'application/json');
        self::assertJson('{"message" : "Authentification par nom et mot de passe nécessaire"}');
    }

    public function testLoginPageReturnTokenKey(): void
    {
        $response = self::createClient()->request('POST', '/login_check', [
            'json' => [
                'username' => 'admin',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $content = $response->toArray();

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $content);
        self::assertResponseHasHeader('Content-Type', 'application/json');
    }

    public function testLoginPageReturnAValidToken(): void
    {
        $container = self::getContainer();

        $encoder = $container->get('lexik_jwt_authentication.encoder');

        $response = self::createClient()->request('POST', '/login_check', [
            'json' => [
                'username' => 'admin',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $jwtIsValid = true;
        try {
            $encoder->decode($response->toArray()['token']);
        } catch (JWTDecodeFailureException) {
            $jwtIsValid = false;
        }

        self::assertResponseIsSuccessful();
        self::assertTrue($jwtIsValid);
    }
}
