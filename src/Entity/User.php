<?php

declare(strict_types = 1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\{PasswordAuthenticatedUserInterface, UserInterface};
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(UserRepository::class), ORM\Table('SERD_Members')]
#[Assert\EnableAutoMapping]
#[ApiResource(
    collectionOperations: [
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    iri: 'https://schema.org/Person',
    itemOperations: [
        'get' => [
            'security' => "is_granted('ROLE_ADMIN') or object.getId() == user.getId()",
        ],
    ],
    denormalizationContext: [
        'groups' => ['user:create'],
        'allow_extra_attributes' => false,
    ],
    normalizationContext: ['groups' => ['user:read']]
)]
class User extends AbstractEntity implements
    UserInterface,
    JWTUserInterface,
    PasswordAuthenticatedUserInterface
{
    use SlugTrait;

    public const MAX_PROJECTS_PER_USER = 5;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Length(min: 3)]
    #[Groups(['user:create', 'user:read', 'project:read', 'project:details'])]
    private string $username = '';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isAdmin = false;

    // This value isn't stored in the database. The password is hashed when data
    // are persisted.
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(min: 6, max: 180)]
    #[Assert\NotCompromisedPassword]
    #[ApiProperty(iri: 'https://schema.org/accessCode')]
    #[Groups('user:create')]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 180)]
    #[Assert\DisableAutoMapping]
    private string $hashedPassword = '';

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email]
    #[Groups(['user:read', 'user:create'])]
    private ?string $email = null;

    #[ORM\OneToMany('user', Project::class, orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $projects;

    #[Pure]
    public function __construct()
    {
        $this->projects = new ArrayCollection;
    }

    public function __toString(): string
    {
        return $this->username;
    }

    #[Pure]
    public function getUserIdentifier(): string
    {
        return $this->toUniqueString();
    }

    /**
     * {@inheritDoc}
     */
    #[Pure]
    public function toUniqueString(): string
    {
        // Because this method is the identifier for UserInterface.
        return $this->getUsername();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles(): array
    {
        $roles = [];

        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param  array<string>  $roles
     */
    public function setRoles(array $roles): self
    {
        if (in_array('ROLE_ADMIN', $roles)) {
            $this->setIsAdmin(true);
        }

        return $this;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword(): ?string
    {
        return $this->hashedPassword;
    }

    public function setHashedPassword(string $hashedPassword): self
    {
        $this->hashedPassword = $hashedPassword;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt(): ?string
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return  Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->contains($project)) {
            $this->projects->removeElement($project);
            // set the owning side to null (unless already changed)
            if ($project->getUser() === $this) {
                $project->setUser(null);
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Create a new instance of this entity from the JWT Payload. Because the
     * payload of JWT cannot contain sensitive information. Method like
     * getHashedPassword WILL ALWAYS return null.
     */
    public static function createFromPayload($username, array $payload): self
    {
        $user = new self;

        $user->setUsername($username);

        if (array_key_exists('roles', $payload)) {
            $user->setRoles($payload['roles']);
        }

        if (array_key_exists('sub', $payload)) {
            $user->setId((int) $payload['sub']);
        }

        return $user;
    }
}
