<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\RequestBody;
use Symfony\Component\HttpFoundation\Response;
use ArrayObject;

final class CreateProjectDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {}

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $pathItem = new PathItem(
            ref: 'Projects',
            post: new Operation(
                operationId: 'postProjectItem',
                tags: ['Project'],
                responses: [
                    Response::HTTP_CREATED => [
                        'description' => 'The project has been created',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Project-project.read',
                                ],
                            ],
                        ]
                    ]
                ],
                summary: 'Create a Project resource.',
                requestBody: new RequestBody(
                    description: 'Create a Project resource.',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Project-project.create',
                            ]
                        ]
                    ])
                )
            )
        );

        $openApi->getPaths()->addPath('/projects', $pathItem);

        return $openApi;
    }
}
