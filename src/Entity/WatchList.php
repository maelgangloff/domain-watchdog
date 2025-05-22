<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\WatchListRepository;
use App\State\WatchListUpdateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WatchListRepository::class)]
#[ApiResource(
    shortName: 'Watchlist',
    operations: [
        new GetCollection(
            routeName: 'watchlist_get_all_mine',
            normalizationContext: ['groups' => [
                'watchlist:list',
                'domain:list',
                'event:list',
            ]],
            name: 'get_all_mine',
        ),
        new GetCollection(
            uriTemplate: '/tracked',
            routeName: 'watchlist_get_tracked_domains',
            normalizationContext: ['groups' => [
                'domain:list',
                'tld:list',
                'event:list',
                'domain:list',
                'event:list',
            ]],
            name: 'get_tracked_domains'
        ),
        new Get(
            normalizationContext: ['groups' => [
                'watchlist:item',
                'domain:item',
                'event:list',
                'domain-entity:entity',
                'nameserver-entity:nameserver',
                'nameserver-entity:entity',
                'tld:item',
            ],
            ],
            security: 'object.user == user'
        ),
        new Get(
            routeName: 'watchlist_calendar',
            openapiContext: [
                'responses' => [
                    '200' => [
                        'description' => 'Watchlist iCalendar',
                        'content' => [
                            'text/calendar' => [
                                'schema' => [
                                    'type' => 'string',
                                    'format' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            read: false,
            deserialize: false,
            serialize: false,
            name: 'calendar'
        ),
        new Post(
            normalizationContext: ['groups' => 'watchlist:list'],
            denormalizationContext: ['groups' => 'watchlist:create'],
            name: 'create',
            processor: WatchListUpdateProcessor::class,
        ),
        new Put(
            normalizationContext: ['groups' => 'watchlist:item'],
            denormalizationContext: ['groups' => ['watchlist:create', 'watchlist:token']],
            security: 'object.user == user',
            name: 'update',
            processor: WatchListUpdateProcessor::class,
            extraProperties: ['standard_put' => false],
        ),
        new Delete(
            security: 'object.user == user'
        ),
    ],
)]
class WatchList
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'watchLists')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?User $user = null;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['watchlist:item', 'watchlist:list', 'watchlist:token'])]
    private string $token;

    /**
     * @var Collection<int, Domain>
     */
    #[ORM\ManyToMany(targetEntity: Domain::class, inversedBy: 'watchLists')]
    #[ORM\JoinTable(name: 'watch_lists_domains',
        joinColumns: [new ORM\JoinColumn(name: 'watch_list_token', referencedColumnName: 'token', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'domain_ldh_name', referencedColumnName: 'ldh_name', onDelete: 'CASCADE')])]
    #[Groups(['watchlist:create', 'watchlist:list', 'watchlist:item'])]
    private Collection $domains;

    /**
     * @var Collection<int, WatchListTrigger>
     */
    #[ORM\OneToMany(targetEntity: WatchListTrigger::class, mappedBy: 'watchList', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    #[SerializedName('triggers')]
    private Collection $watchListTriggers;

    #[ORM\ManyToOne(inversedBy: 'watchLists')]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    private ?Connector $connector = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['watchlist:list', 'watchlist:item'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[SerializedName('dsn')]
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    private ?array $webhookDsn = null;

    public function __construct()
    {
        $this->token = Uuid::v4();
        $this->domains = new ArrayCollection();
        $this->watchListTriggers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
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

    /**
     * @return Collection<int, WatchListTrigger>
     */
    public function getWatchListTriggers(): Collection
    {
        return $this->watchListTriggers;
    }

    public function addWatchListTrigger(WatchListTrigger $watchListTrigger): static
    {
        if (!$this->watchListTriggers->contains($watchListTrigger)) {
            $this->watchListTriggers->add($watchListTrigger);
            $watchListTrigger->setWatchList($this);
        }

        return $this;
    }

    public function removeWatchListTrigger(WatchListTrigger $watchListTrigger): static
    {
        if ($this->watchListTriggers->removeElement($watchListTrigger)) {
            // set the owning side to null (unless already changed)
            if ($watchListTrigger->getWatchList() === $this) {
                $watchListTrigger->setWatchList(null);
            }
        }

        return $this;
    }

    public function getConnector(): ?Connector
    {
        return $this->connector;
    }

    public function setConnector(?Connector $connector): static
    {
        $this->connector = $connector;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getWebhookDsn(): ?array
    {
        return $this->webhookDsn;
    }

    public function setWebhookDsn(?array $webhookDsn): static
    {
        $this->webhookDsn = $webhookDsn;

        return $this;
    }
}
