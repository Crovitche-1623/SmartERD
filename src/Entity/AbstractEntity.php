<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @noinspection PhpUndefinedClassInspection */
#[ORM\MappedSuperclass]
abstract class AbstractEntity implements \Stringable
{
    use IdTrait;

    abstract public function __toString(): string;
}
