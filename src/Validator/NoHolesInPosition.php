<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute]
final class NoHolesInPosition extends Constraint
{
    public const MESSAGE = "You can't set the position greater than 1 from the last one. Last position is {{ lastPosition }}, position given is {{ givenPosition }}";
    public const MESSAGE_IF_NO_OTHER_ATTRIBUTES = "You have specified a position but there are no other data so please don't specify any position";

    public function __construct(
        public ?string $sortableGroupProperty,
        array $groups = null,
        $payload = null
    )
    {
        $options['sortableGroupProperty'] = $this->sortableGroupProperty;
        parent::__construct($options, $groups, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
    {
        return NoHolesInPositionValidator::class;
    }
}
