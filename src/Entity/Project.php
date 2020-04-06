<?php

declare(strict_types = 1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\{ApiResource, ApiProperty};
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass = "App\Repository\ProjectRepository")
 * @ApiResource(
 *     iri = "https://schema.org/Project",
 *     collectionOperations = {"post"},
 *     subresourceOperations = {
 *         "api_users_created_projects_get_subresource" = {
 *             "method" = "GET",
 *             "normalization_context" = {"groups" = {"project:read"}}
 *         }
 *     },
 *     itemOperations = {
 *         "get" = {
 *             "security" = "user == object.user or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only access project your projects data unless you're an admin."
 *         },
 *         "patch" = {
 *             "security" = "user == object.user or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only update your projects unless you're an admin."
 *         },
 *         "delete" = {
 *             "security" = "user == object.user or is_granted('ROLE_ADMIN')",
 *             "security_message" = "You can only delete your projects unless you're an admin."
 *         }
 *     },
 *     normalizationContext = {"groups" : {"project:read"}},
 *     denormalizationContext = {"groups" : {"project:create"}}
 * )
 */
class Project
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type = "integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(length = 50)
     *
     * @ApiProperty(iri = "https://schema.org/title")
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
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
