<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use App\Repository\EntityRepository;
use App\Validator as CustomAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(EntityRepository::class)]
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
        ]
    ],
    denormalizationContext: ['groups' => 'entity:create'],
    normalizationContext: ['groups' => 'entity:read'],
)]
#[Assert\EnableAutoMapping]
class Entity extends AbstractEntity implements UniqueStringableInterface
{
    #[ORM\ManyToOne(
        targetEntity: Project::class,
        fetch: 'EAGER',
        inversedBy: 'entities'
    )]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['entity:create'])]
    #[CustomAssert\MaxEntries(30)]
    private ?Project $project = null;

    #[ORM\Column(length: 180)]
    #[ApiProperty(iri: 'https://schema.org/name')]
    #[Groups([
        'project:details',
        'entity:create', 'entity:read', 'entity:edit'
    ])]
    #[Assert\Regex(
        pattern: '/^[a-z]+$/i',
        htmlPattern: '^[a-zA-Z]+$'
    )]
    private ?string $name = null;

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
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
