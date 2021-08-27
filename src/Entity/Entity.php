<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use JetBrains\PhpStorm\Pure;
use App\{Repository\EntityRepository, Validator as CustomAssert};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(EntityRepository::class), ORM\Table('SERD_Entities')]
#[ORM\UniqueConstraint('uniq___entity___name__project', ['name', 'project_id'])]
#[UniqueEntity(
    fields: ['project', 'name'],
    message: 'You already created an entity with this name {{ value }}',
    errorPath: 'name',
)]
#[ApiResource(
    collectionOperations: ['post'],
    itemOperations: [
        'get' => ['security' => "is_granted('ENTITY_VIEW', object)"],
        'patch' => [
            'security' => "is_granted('ENTITY_VIEW', object)",
            'denormalization_context' => ['groups' => 'entity:edit']
        ],
        'delete' => [
            'security' => "is_granted('ENTITY_VIEW', object)"
        ]
    ],
    denormalizationContext: ['groups' => 'entity:create'],
    normalizationContext: ['groups' => 'entity:read'],
)]
#[Assert\EnableAutoMapping]
class Entity extends AbstractEntity
{
    #[ORM\ManyToOne(
        targetEntity: Project::class,
        fetch: 'EAGER',
        inversedBy: 'entities'
    )]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['entity:create'])]
    #[CustomAssert\MaxEntries(Project::MAX_ENTITIES_PER_PROJECT)]
    private ?Project $project = null;

    #[ORM\Column(length: 180)]
    #[ApiProperty(iri: 'https://schema.org/name')]
    #[Groups([
        'project:details',
        'entity:create', 'entity:read', 'entity:edit'
    ])]
    #[Assert\Regex('/^[a-z]+$/i', htmlPattern: '^[a-zA-Z]+$')]
    private ?string $name = null;

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
        return $this->getName().' '.$this->project->getName();
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
