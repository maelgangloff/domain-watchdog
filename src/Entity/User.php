<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var array The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, BookmarkList>
     */
    #[ORM\OneToMany(targetEntity: BookmarkList::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $bookmarkDomainLists;

    public function __construct()
    {
        $this->bookmarkDomainLists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, BookmarkList>
     */
    public function getBookmarkDomainLists(): Collection
    {
        return $this->bookmarkDomainLists;
    }

    public function addBookmarkDomainList(BookmarkList $bookmarkDomainList): static
    {
        if (!$this->bookmarkDomainLists->contains($bookmarkDomainList)) {
            $this->bookmarkDomainLists->add($bookmarkDomainList);
            $bookmarkDomainList->setUser($this);
        }

        return $this;
    }

    public function removeBookmarkDomainList(BookmarkList $bookmarkDomainList): static
    {
        if ($this->bookmarkDomainLists->removeElement($bookmarkDomainList)) {
            // set the owning side to null (unless already changed)
            if ($bookmarkDomainList->getUser() === $this) {
                $bookmarkDomainList->setUser(null);
            }
        }

        return $this;
    }
}
