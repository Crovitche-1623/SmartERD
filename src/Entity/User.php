<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass = "App\Repository\UserRepository")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type = "integer")
     *
     * ID can be nullable because the id is only received after the data has
     * been persisted.
     */
    private ?int $id = null;

    /**
     * @ORM\Column(length = 180, unique = true)
     */
    private string $username = '';

    /**
     * @ORM\Column(type = "boolean")
     */
    private bool $isAdmin = false;

    /**
     * @ORM\Column(length = 180)
     */
    private string $password = '';

    /**
     * @ORM\Column(length = 180, unique = true)
     */
    private string $email = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
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
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
