<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

final class ProjectInput
{
    #[Groups(['project:create'])]
    public ?string $name = null;
}
