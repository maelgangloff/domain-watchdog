<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\WatchListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WatchListRepository::class)]
#[ApiResource(
    shortName: 'Watchlist',
    normalizationContext: ['groups' => 'watchlist:item', 'domain:list'],
    denormalizationContext: ['groups' => 'watchlist:item', 'domain:list'],
    paginationEnabled: false
)]
class WatchList
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[Groups(['watchlist:item'])]
    private string $token;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'watchLists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Domain>
     */
    #[ORM\ManyToMany(targetEntity: Domain::class, inversedBy: 'watchLists')]
    #[ORM\JoinTable(name: 'watch_lists_domains',
        joinColumns: [new ORM\JoinColumn(name: 'watch_list_token', referencedColumnName: 'token')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'domain_ldh_name', referencedColumnName: 'ldh_name')])]
    #[Groups(['watchlist:item'])]
    private Collection $domains;

    public function __construct()
    {
        $this->token = Uuid::v4();
        $this->domains = new ArrayCollection();
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Domain>
     */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): static
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
        }

        return $this;
    }

    public function removeDomain(Domain $domain): static
    {
        $this->domains->removeElement($domain);

        return $this;
    }
}
