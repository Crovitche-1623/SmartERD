<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute] final class Position extends Constraint
{
    public const MESSAGE = 'Unable to create/define this {{ entityName }} at the last position. The maximum occurrence has been reached or the position of a(n) {{ entityName }} has been set to the maximum.';
    public const MESSAGE_IF_SORTABLE_GROUP = 'Unable to create/define this {{ entityName }} for this {{ sortableGroupProperty }} at the last position. The maximum occurrence has been reached or the position of a(n) {{ entityName }} has been set to the maximum.';

    public function __construct(
        public int $max,
        public ?string $sortableGroupProperty = null,
        array $groups = null,
        mixed $payload = null
    )
    {
        $options['max'] = $this->max;
        $options['sortableGroupProperty'] = $this->sortableGroupProperty;
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
        return PositionValidator::class;
    }
}
