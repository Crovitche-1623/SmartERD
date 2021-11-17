<?php

declare(strict_types=1);

namespace App\Validator\Constraints\Project;

use App\Entity\User;
use App\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Provide project constraints with Assert\Sequentially which is not available
 * because of the php8.1-fpm-alpine image not ready yet.
 *
 * @Annotation
 */
#[\Attribute] final class ProjectUserRequirements extends Compound
{
    /**
     * {@inheritDoc}
     *
     * @param  array{}  $options  empty array yet
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotNull(message: <<<TXT
A null value has been provided or no user has been found with the data provided.
TXT
                ),
                new Assert\Type(User::class),
                new CustomAssert\MaxEntries(User::MAX_PROJECTS_PER_USER, 'user'),
            ]),
        ];
    }
}
