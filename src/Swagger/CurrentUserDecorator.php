<?php

declare(strict_types=1);

namespace App\Swagger;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CurrentUserDecorator implements NormalizerInterface
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

        $docs['paths']['/projects']['get']['summary'] = "Retrieve the projects of current user if he's not admin";

        return $docs;
    }
}