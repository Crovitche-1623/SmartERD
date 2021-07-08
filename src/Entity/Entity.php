<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use App\Repository\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass = EntityRepository::class)
 * @ORM\Table(
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uniq___entity___name__project",
 *             columns = {"name", "project_id"}
 *         )
 *     }
 * )
 *
 * @UniqueEntity(
 *     fields = {"project", "name"},
 *     errorPath = "name",
 *     message = "You already created an entity with this name {{ value }}"
 * )
 *
 * @ApiResource(
 *     normalizationContext = {"groups" : {"entity:read"}},
 *     denormalizationContext = {"groups" : {"entity:create"}},
 *     itemOperations = {
 *         "get" = {"security" = "is_granted('ENTITY_VIEW', object)"}
 *     },
 *     collectionOperations = {
 *         "post"
 *     }
 * )
 *
 * @Assert\EnableAutoMapping
 */
class Entity implements UniqueStringableInterface
{
    use IdTrait;

    /**
     * @ORM\ManyToOne(targetEntity = Project::class, inversedBy = "entities")
     * @ORM\JoinColumn(nullable = false)
     *
     * @Groups({"entity:create"})
     *
     * TODO: Create an event listener to change the way the iri is retrieved.
     *       It MUST return a "Not Found" exception if the project doesn't
     *       belongs to the current user or the current user is not an
     *       administrator
     *       The Doctrine Extension seems to work - Write a functional test.
     */
    private ?Project $project = null;

    /**
     * @ORM\Column(length = 180)
     *
     * @ApiProperty(iri = "https://schema.org/name")
     *
     * @Groups({"project:details", "entity:create", "entity:read"})
     *
     * // TODO: Only accept [a-z] and [A-Z] letters
     */
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

    /**
     * The Assert\Count validation is only available on the side the entity are
     * persisted. So we must create a callback to check here instead.
     *
     * @Assert\Callback
     *
     * @param  ExecutionContextInterface  $context
     */
    public function LimitMaxEntitiesPerProject(
        ExecutionContextInterface $context
    ): void
    {
        $maxAllowed = Project::MAX_ENTITIES_PER_PROJECT;
        // TODO: Check if this following line make a query which obtain the
        //       whole collection and the count. Otherwise create a custom
        //       validator instead.
        $entitiesCount = $this->project->getEntities()->count();
        if ($entitiesCount >= $maxAllowed) {
            // TODO: Check why the title of error is "An error occurred" instead
            //       of "Validation failed".
            $context->buildViolation(
                sprintf(
                    'The maximum number of entities per project is %d.',
                    $maxAllowed
                )
            )
                ->atPath('project')
                ->addViolation();
        }
    }
}