<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Security\JsonAuthenticatorTest;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProjectsTest extends ApiTestCase
{
    private string $JWTToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->JWTToken = JsonAuthenticatorTest::login();
    }

    public function testCreateProject(): void
    {
        $response = static::createClient()->request('POST', '/projects', [
            'json' => [
                'title' => 'Test Project'
            ]
        ]);

        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonContains([

        ]);
    }
}