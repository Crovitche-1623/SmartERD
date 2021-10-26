<?php

declare(strict_types = 1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use App\Dto\ProjectInput;
use App\Repository\ProjectRepository;
use App\Validator as CustomAssert;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(ProjectRepository::class), ORM\Table('SERD_Projects')]
#[ORM\UniqueConstraint('uniq___project___name__user', ['name', 'user_id'])]
#[Assert\EnableAutoMapping]
#[UniqueEntity(
    fields: ['user', 'name'],
    message: "You have already created a project with this name {{ value }}",
    errorPath: 'name',
)]
// N.B: All the "security" annotation below is an additional security because
//      an additional "where" close is added foreach DQL query.
#[ApiResource(
    collectionOperations: [
        'get',
        'post' => [
            'input' => ProjectInput::class,
            'normalization_context' => ['groups' => ['project:read']],
            'denormalization_context' => ['groups' => ['project:create']]
        ],
    ],
    iri: 'https://schema.org/Project',
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['project:details']],
            'security_message' => "You can only access your projects data unless you're an admin."
        ],
        'patch' => [
            'security_message' => "You can only update your projects unless you're an admin."
        ],
        'delete' => [
            'security_message' => "You can only delete your projects unless you're an admin."
        ]
    ],
    denormalizationContext: [
        'groups' => 'project:create',
        'allow_extra_attributes' => false
    ],
    normalizationContext: ['groups' => 'project:read'],
)]
class Project extends AbstractEntity
{
    use SlugTrait;

    public const MAX_ENTITIES_PER_PROJECT = 30;

    #[ORM\Column(length: 50)]
    #[ApiProperty(iri: 'https://schema.org/name')]
    #[Groups(['project:create', 'project:read', 'project:details'])]
    #[Assert\NotBlank]
    #[Assert\Type("string")]
    #[Assert\Length(max: 50)]
    private ?string $name = null;

    #[ORM\ManyToOne(User::class, fetch: 'EAGER', inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    #[CustomAssert\MaxEntries(User::MAX_PROJECTS_PER_USER)]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    #[Groups(['project:read', 'project:details'])]
    private ?User $user = null;

    // Note: This validation only apply when entities are persisted from a
    //       project.
    #[ORM\OneToMany(
        mappedBy: "project",
        targetEntity: Entity::class,
        orphanRemoval: true
    )]
    #[Groups('project:details')]
    #[Assert\Count(
        max: self::MAX_ENTITIES_PER_PROJECT,
        maxMessage: 'You cannot have more than {{ limit }} entities'
    )]
    private Collection $entities;

    #[Pure]
    public function __construct()
    {
        $this->entities = new ArrayCollection;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    #[Pure]
    public function toUniqueString(): string
    {
        return $this->getName().' '.$this->user->getUsername();
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return  Collection<int, Entity>
     */
    public function getEntities(): Collection
    {
        return $this->entities;
    }

    public function addEntity(Entity $entity): self
    {
        if (!$this->entities->contains($entity)) {
            $this->entities[] = $entity;
            $entity->setProject($this);
        }

        return $this;
    }

    public function removeEntity(Entity $entity): self
    {
        if ($this->entities->contains($entity)) {
            $this->entities->removeElement($entity);
            // set the owning side to null (unless already changed)
            if ($entity->getProject() === $this) {
                $entity->setProject(null);
            }
        }

        return $this;
    }
}
