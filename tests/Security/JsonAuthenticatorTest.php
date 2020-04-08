<?php

declare(strict_types=1);

namespace App\Tests\Security;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Make sure you have created the database before running the tests.
 */
final class JsonAuthenticatorTest extends ApiTestCase
{
    use FixturesTrait;

    private bool $userFixturesHasBeenLoaded = false;

    private function loadUserFixturesIfNotAlreadyDone(): void
    {
        if (!$this->userFixturesHasBeenLoaded) {
            $this->loadFixtures([
                UserFixtures::class
            ]);

            $this->userFixturesHasBeenLoaded = true;
        }
    }

    public static function login(): string // return a JWT Token
    {
        $response = static::createClient()->request('POST', '/login', [
            'json' => [
                'username' => 'admin',
                'password' => UserFixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        return $response->toArray()['token'];
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
        $this->loadUserFixturesIfNotAlreadyDone();

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
        $this->loadUserFixturesIfNotAlreadyDone();

        self::bootKernel();
        $encoder = self::$container->get('lexik_jwt_authentication.encoder.lcobucci');

        $jwtIsValid = true;
        try {
            $encoder->decode($this->login());
        } catch (JWTDecodeFailureException $exception) {
            $jwtIsValid = false;
        }

        $this->assertResponseIsSuccessful();
        $this->assertTrue($jwtIsValid);
    }
}