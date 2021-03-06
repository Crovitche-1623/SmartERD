<?php

declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass = UserRepository::class)
 * @ORM\Table(name = "member") To avoid the reserved SQL word "user"
 * @Assert\EnableAutoMapping
 */
class User implements UserInterface, JWTUserInterface, UniqueStringableInterface
{
    use IdTrait;

    /**
     * @ORM\Column(length = 180, unique = true)
     *
     * @Assert\Length(min = 3)
     */
    private string $username = '';

    /**
     * @ORM\Column(type = "boolean")
     */
    private bool $isAdmin = false;

    /**
     * This value isn't stored in the database. The password is hashed when data
     * are persisted.
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(min = 6, max = 180)
     * @Assert\NotCompromisedPassword
     */
    private ?string $plainPassword = null;

    /**
     * @ORM\Column(length = 180)
     *
     * @Assert\DisableAutoMapping
     */
    private string $hashedPassword = '';

    /**
     * @ORM\Column(length = 180, unique = true)
     *
     * @Assert\Email
     */
    private ?string $email = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity = Project::class,
     *     mappedBy = "user",
     *     orphanRemoval = true
     * )
     */
    private Collection $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection;
    }

    public function __toString(): string
    {
        return $this->username;
    }

    /**
     * {@inheritDoc}
     */
    public function toUniqueString(): string
    {
        // Because this method is the identifier for UserInterface.
        return $this->getUsername();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see  UserInterface
     */
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
     * @return  Collection|Project[]
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
        return (new self)
            ->setUsername($username)
            ->setRoles($payload['roles'])
            ->setId((int) $payload['sub']);
    }
}
