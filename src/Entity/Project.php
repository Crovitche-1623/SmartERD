<?php

declare(strict_types = 1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiResource, ApiProperty};
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass = ProjectRepository::class)
 * @ORM\Table(
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uniq___project___name__user",
 *             columns = {"name", "user_id"}
 *         )
 *     }
 * )
 *
 * @Assert\EnableAutoMapping
 *
 * @UniqueEntity(
 *     fields = {"user", "name"},
 *     errorPath = "name",
 *     message = "You have already created a project with this name {{ value }}",
 * )
 *
 * N.B: All the "security" annotation below is an additional security because
 *      an additional "where" close is added foreach DQL query.
 * @ApiResource(
 *     attributes = {
 *         "pagination_items_per_page" = ProjectRepository::ITEM_PER_PAGE
 *     },
 *     iri = "https://schema.org/Project",
 *     normalizationContext = {"groups" : {"project:read"}},
 *     denormalizationContext = {"groups" : {"project:create"}},
 *     collectionOperations = {
 *         "get"
 *     },
 *     itemOperations = {
 *         "get" = {
 *             "normalization_context" = {"groups" = {"project:details"}},
 *             "security" = "user == object.getUser() or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only access your projects data unless you're an admin."
 *         },
 *         "patch" = {
 *             "security" = "user == object.getUser() or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only update your projects unless you're an admin."
 *         },
 *         "delete" = {
 *             "security" = "user == object.getUser() or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only delete your projects unless you're an admin."
 *         }
 *     }
 * )
 */
class Project implements UniqueStringableInterface
{
    use IdTrait;

    const MAX_ENTITIES_PER_PROJECT = 30;

    /**
     * @ORM\Column(length = 50)
     *
     * @ApiProperty(iri = "https://schema.org/name")
     *
     * @Groups({"project:create", "project:read", "project:details"})
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity = User::class, inversedBy = "projects")
     * @ORM\JoinColumn(nullable = false)
     */
    private ?UserInterface $user = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity = Entity::class,
     *     mappedBy = "project",
     *     orphanRemoval = true
     * )
     *
     * Warning : The nested entities collection are not paginated.
     * Note: This validation only apply when entities are persisted from a
     *       project.
     * @Assert\Count(
     *     max = 30,
     *     maxMessage = "You cannot have more than {{ limit }} entities"
     * )
     * @Groups("project:details")
     */
    private Collection $entities;

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

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return  Entity[]|Collection
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
