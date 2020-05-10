<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generate a simple auto incremented integer column which is (or part of) the
 * primary key. This trait is useful to save some line of recurring code.
 */
trait IdTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type = "integer")
     *
     * ID can be nullable because the id is only received after the data has
     * been persisted.
     */
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}