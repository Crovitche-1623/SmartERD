<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[Attribute] final class MaxEntries extends Constraint
{
    public const MESSAGE = 'The maximum number of {{ parentName }} ({{ max }}) for this {{ childName }} has been reached.';

    public function __construct(
        public int $max,
        public ?string $property = null,
        array $groups = null,
        mixed $payload = null
    )
    {
        $options['max'] = $this->max;
        $options['property'] = $this->property;
        parent::__construct($options, $groups, $payload);
    }

    public function getRequiredOptions(): array
    {
        return ['max'];
    }

    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
    {
        return MaxEntriesValidator::class;
    }
}
