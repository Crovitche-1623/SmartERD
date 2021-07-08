<?php

declare(strict_types=1);

namespace App\Swagger;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CreateProjectDecorator implements NormalizerInterface
{
    private NormalizerInterface $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(
        $object,
        string $format = null,
        array $context = []
    ): array
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        $createProjectDocumentation = [
            'paths' => [
                '/projects' => [
                    'post' => [
                        'tags' => ['Project'],
                        'operationId' => 'postProjectItem',
                        'summary' => 'Create a Project resource.',
                        'requestBody' => [
                            'description' => 'Create a Project resource.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Project-project:create',
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            Response::HTTP_CREATED => [
                                'description' => 'The project has been created',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/Project-project:read',
                                        ],
                                    ],
                                ],
                            ],
                            Response::HTTP_BAD_REQUEST => [
                                'description' => 'The project details are incorrect or the project already exist',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return array_merge_recursive($docs, $createProjectDocumentation);
    }
}