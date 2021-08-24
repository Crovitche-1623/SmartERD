<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\{
    Factory\OpenApiFactoryInterface,
    Model\Operation,
    Model\PathItem,
    Model\RequestBody,
    OpenApi
};
use ArrayObject;
use Symfony\Component\HttpFoundation\Response;

final class ProjectDecorator implements OpenApiFactoryInterface
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
            get: new Operation(
                operationId: 'getProjectItems',
                tags: ['Project'],
                responses: [
                    Response::HTTP_OK => [
                        'description' => 'Projects list of the user who call the request.',
                        'content' => [
                            'application/ld+json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Project.jsonld-project.read'
                                ],
                            ],
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Project-project.read'
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'List the projects of the user who call the request.'
            ),
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
