<?php

declare(strict_types=1);

namespace App\Service;

use Hidehalo\Nanoid\Client;

final class SlugGeneratorService
{
    public const SLUG_LENGTH = 21;

    public function __invoke(int $length = self::SLUG_LENGTH): string
    {
        return (new Client)->generateId(
            size: $length,
            mode: Client::MODE_DYNAMIC
        );
    }
}
