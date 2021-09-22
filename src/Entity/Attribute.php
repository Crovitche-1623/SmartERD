<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AttributeSortableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as CustomAssert;

#[ORM\Entity(AttributeSortableRepository::class), ORM\Table('SERD_Attributes')]
#[ApiResource(
    // TODO: Check security
    collectionOperations: ['post'],
    itemOperations: [
        'get',
        'patch',
        'delete'
    ],
    denormalizationContext: [
        'groups' => 'attribute:create',
    ],
    normalizationContext: ['groups' => 'attribute:read'],
)]
class Attribute extends AbstractEntity
{
    use SlugTrait;

    #[ORM\Column(length: 180)]
    #[ApiProperty(iri: 'https://schema.org/name')]
    #[Groups(['attribute:read', 'attribute:create', 'project:details'])]
    #[Assert\Regex('/^[a-z]+$/i', htmlPattern: '^[a-zA-Z]+$')]
    private ?string $name = null;

    /** @Gedmo\SortablePosition */
    #[ORM\Column(type: Types::SMALLINT)]
    #[ApiProperty(iri: 'https://schema.org/position')]
    #[Groups(['attribute:read', 'attribute:create', 'project:details'])]
    #[Assert\Range(min: -1, max: Entity::MAX_ATTRIBUTES_PER_ENTITY)]
    #[CustomAssert\Position(max: Entity::MAX_ATTRIBUTES_PER_ENTITY, sortableGroupProperty: 'entity')]
    #[CustomAssert\NoHolesInPosition(sortableGroupProperty: 'entity')]
    private int $position = -1;

    /** @Gedmo\SortableGroup */
    #[ORM\ManyToOne(Entity::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['attribute:read', 'attribute:create'])]
    private ?Entity $entity = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }

    public function setEntity(?Entity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    #[Pure]
    public function __toString(): string
    {
        return (string) $this->getName();
    }

    /**
     * {@inheritDoc}
     */
    #[Pure]
    public function toUniqueString(): string
    {
        return $this->__toString() . ' ' . $this->entity->toUniqueString();
    }
}
