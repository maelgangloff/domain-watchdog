<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\MeController;
use App\Controller\RegistrationController;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me',
            controller: MeController::class,
            normalizationContext: ['groups' => 'user:list'],
            read: false
        ),
        new Post(
            uriTemplate: '/register',
            routeName: 'user_register',
            controller: RegistrationController::class,
            denormalizationContext: ['groups' => ['user:register']],
            read: false,
            name: 'register'
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:list', 'user:register'])]
    private ?string $email = null;

    /**
     * @var array The user roles
     */
    #[ORM\Column]
    #[Groups(['user:list'])]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['user:register'])]
    private ?string $password = null;

    /**
     * @var Collection<int, WatchList>
     */
    #[ORM\OneToMany(targetEntity: WatchList::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $watchLists;

    /**
     * @var Collection<int, Connector>
     */
    #[ORM\OneToMany(targetEntity: Connector::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $connectors;

    #[ORM\Column]
    private bool $isVerified = false;

    public function __construct()
    {
        $this->watchLists = new ArrayCollection();
        $this->connectors = new ArrayCollection();
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
     * @return list<string>
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
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
     * @return Collection<int, WatchList>
     */
    public function getWatchLists(): Collection
    {
        return $this->watchLists;
    }

    public function addWatchList(WatchList $watchList): static
    {
        if (!$this->watchLists->contains($watchList)) {
            $this->watchLists->add($watchList);
            $watchList->setUser($this);
        }

        return $this;
    }

    public function removeWatchList(WatchList $watchList): static
    {
        if ($this->watchLists->removeElement($watchList)) {
            // set the owning side to null (unless already changed)
            if ($watchList->getUser() === $this) {
                $watchList->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Connector>
     */
    public function getConnectors(): Collection
    {
        return $this->connectors;
    }

    public function addConnector(Connector $connector): static
    {
        if (!$this->connectors->contains($connector)) {
            $this->connectors->add($connector);
            $connector->setUser($this);
        }

        return $this;
    }

    public function removeConnector(Connector $connector): static
    {
        if ($this->connectors->removeElement($connector)) {
            // set the owning side to null (unless already changed)
            if ($connector->getUser() === $this) {
                $connector->setUser(null);
            }
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
