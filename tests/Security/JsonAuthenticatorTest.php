<?php

declare(strict_types=1);

namespace App\Tests\Security;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Make sure you have created the database before running the tests.
 */
final class JsonAuthenticatorTest extends ApiTestCase
{
    use FixturesTrait;

    private bool $userFixturesHaveBeenLoaded = false;
    private HttpClientInterface $client;

    public function setUp(): void
    {
        $this->loadUserFixturesIfNotAlreadyDone();
    }

    private function loadUserFixturesIfNotAlreadyDone(): void
    {
        if (!$this->userFixturesHaveBeenLoaded) {
            $this->loadFixtures([
                UserFixtures::class
            ]);

            $this->userFixturesHaveBeenLoaded = true;
        }
    }

    public static function login(bool $loginAsAdmin = true): HttpClientInterface
    {
        $response = static::createClient()->request('POST', '/login', [
            'json' => [
                'username' => $loginAsAdmin ? 'admin' : 'user',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        return static::createClient([], [
            'auth_bearer' => $response->toArray()['token']
        ]);
    }

    public function testPrivatePageIsInaccessible(): void
    {
        static::createClient()->request('GET', '/');

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_UNAUTHORIZED);
        $this->assertResponseHasHeader('Content-Type', 'application/json');
        $this->assertJson('{"message" : "Authentification par nom et mot de passe nécessaire"}');
    }

    public function testLoginPageReturnTokenKey(): void
    {
        $response = static::createClient()->request('POST', '/login', [
            'json' => [
                'username' => 'admin',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $content = $response->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $content);
        $this->assertResponseHasHeader('Content-Type', 'application/json');
    }

    public function testLoginPageReturnAValidToken(): void
    {
        self::bootKernel();
        $encoder = self::$container->get('lexik_jwt_authentication.encoder.lcobucci');

        $response = static::createClient()->request('POST', '/login', [
            'json' => [
                'username' => 'admin',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $jwtIsValid = true;
        try {
            $encoder->decode($response->toArray()['token']);
        } catch (JWTDecodeFailureException $exception) {
            $jwtIsValid = false;
        }

        $this->assertResponseIsSuccessful();
        $this->assertTrue($jwtIsValid);
    }
}