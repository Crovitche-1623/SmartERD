<?php

declare(strict_types=1);

namespace App\Tests\Functional\AsAnonymous\Security;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\UserFixtures as Fixtures;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Make sure you have created the database before running the tests.
 */
final class JsonAuthenticatorTest extends ApiTestCase
{
    /**
     * @throws  TransportExceptionInterface
     */
    public function testPrivatePageIsInaccessible(): void
    {
        self::createClient()->request('GET', '/');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertResponseHasHeader('Content-Type', 'application/json');
        self::assertJson('{"message" : "Authentification par nom et mot de passe nécessaire"}');
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testLoginPageReturnTokenKey(): void
    {
        $response = self::createClient()->request('POST', '/login_check', [
            'json' => [
                'username' => Fixtures::ADMIN_USERNAME,
                'password' => Fixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $content = $response->toArray();

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $content);
        self::assertResponseHasHeader('Content-Type', 'application/json');
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testLoginPageReturnAValidToken(): void
    {
        $response = self::createClient()->request('POST', '/login_check', [
            'json' => [
                'username' => Fixtures::ADMIN_USERNAME,
                'password' => Fixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $encoder = self::getContainer()->get('lexik_jwt_authentication.encoder');

        $jwtIsValid = true;
        try {
            $encoder->decode($response->toArray()['token']);
        } catch (JWTDecodeFailureException) {
            $jwtIsValid = false;
        }

        self::assertResponseIsSuccessful();
        self::assertTrue($jwtIsValid);
    }

    /**
     * @depends  testLoginPageReturnAValidToken
     *
     * @throws  JWTDecodeFailureException
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testJwtPayloadContainsSubClaim(): void
    {
        $response = self::createClient()->request('POST', '/login_check', [
            'json' => [
                'username' => Fixtures::USER_USERNAME,
                'password' => Fixtures::DEFAULT_USER_PASSWORD
            ]
        ]);

        $token = self::getContainer()
            ->get(JWTTokenManagerInterface::class)
            ->parse($response->toArray()['token'])
        ;

        self::assertNotEmpty($token['sub']);
    }
}
