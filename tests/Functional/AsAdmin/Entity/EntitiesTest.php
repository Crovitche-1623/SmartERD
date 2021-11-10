<?php

declare(strict_types=1);

namespace App\Tests\Functional\AsAdmin\Entity;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\DataFixtures\ProjectFixtures;
use App\Entity\Project;
use App\Service\JwtTokenGeneratorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EntitiesTest extends ApiTestCase
{
    private HttpClientInterface $client;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        $container = self::getContainer();

        $this->client = self::createClient(defaultOptions: [
            'auth_bearer' => $container->get(JwtTokenGeneratorService::class)(asAdmin: true)
        ]);

        parent::setUp();
    }

    /**
     * @throws  TransportExceptionInterface
     * @throws  ServerExceptionInterface
     * @throws  RedirectionExceptionInterface
     * @throws  DecodingExceptionInterface
     * @throws  ClientExceptionInterface
     */
    public function testNameWithNotOnlyAlphabeticalCharacter(): void
    {
        $adminProjectIri = $this->findIriBy(Project::class, [
            'name' => ProjectFixtures::ADMIN_PROJECT_NAME
        ]);

        $this->client->request('POST', '/entities', [
            'json' => [
                /** Entité contains é which is an accented character */
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
}
