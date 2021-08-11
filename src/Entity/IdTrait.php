<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// Generate a simple auto incremented integer column which is (or part of) the
// primary key. This trait is useful to save some line of recurring code.
trait IdTrait
{
    // ID can be nullable because the id is only received after the data has
    // been persisted.
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    // This method won't be available if you use the database less provider
    // for JWT.
    public function getId(): ?int
    {
        return $this->id;
    }

    // Warning: This method should be only used to define ID from JWT Payload.
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
