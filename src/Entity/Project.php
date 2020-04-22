<?php

declare(strict_types = 1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiResource, ApiProperty};
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass = "App\Repository\ProjectRepository")
 * @ORM\Table(
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uniq___project___title__user",
 *             columns = {"title", "user_id"}
 *         )
 *     }
 * )
 *
 * @UniqueEntity(
 *     fields = {"user", "title"},
 *     errorPath = "title",
 *     message = "You have already created a project with this name {{ value }}",
 * )
 *
 * @ApiResource(
 *     iri = "https://schema.org/Project",
 *     collectionOperations = {},
 *     subresourceOperations = {
 *         "api_users_created_projects_get_subresource" = {
 *             "method" = "GET",
 *             "normalization_context" = {"groups" = {"project:read"}}
 *         }
 *     },
 *     itemOperations = {
 *         "get" = {
 *             "security" = "user == object.getUser() or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only access project your projects data unless you're an admin."
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
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(min = 1, max = 50)
     *
     * @ApiProperty(iri = "https://schema.org/title")
     *
     * @Groups({"project:create", "project:read", "user:read"})
     */
    private string $title = '';

    /**
     * @ORM\ManyToOne(
     *     targetEntity = "App\Entity\User",
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
