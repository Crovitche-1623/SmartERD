<?php

declare(strict_types = 1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiResource, ApiProperty};
use App\Repository\ProjectRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

/**
 * @ORM\Entity(repositoryClass = ProjectRepository::class)
 * @ORM\Table(
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uniq___project___title__user",
 *             columns = {"title", "user_id"}
 *         )
 *     }
 * )
 *
 * @Assert\EnableAutoMapping
 *
 * @UniqueEntity(
 *     fields = {"user", "title"},
 *     errorPath = "title",
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
 *     collectionOperations = {
 *         "get"
 *     },
 *     itemOperations = {
 *         "get" = {
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
 *     },
 *     normalizationContext = {"groups" : {"project:read"}},
 *     denormalizationContext = {"groups" : {"project:create"}}
 * )
 */
class Project
{
    use IdTrait;

    /**
     * @ORM\Column(length = 50)
     *
     * @ApiProperty(iri = "https://schema.org/title")
     *
     * @Groups({"project:create", "project:read"})
     */
    private string $title = '';

    /**
     * @ORM\ManyToOne(
     *     targetEntity = User::class,
     *     inversedBy = "projects"
     * )
     * @ORM\JoinColumn(nullable = false)
     */
    private ?UserInterface $user = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }
}
