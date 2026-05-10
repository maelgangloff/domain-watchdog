<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\WatchlistRepository;
use App\State\MyWatchlistsProvider;
use App\State\WatchlistUpdateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WatchlistRepository::class)]
#[ApiResource(
    shortName: 'Watchlist',
    operations: [
        new GetCollection(
            openapiContext: [
                'summary' => 'Retrieve all my Watchlists',
                'description' => 'This endpoint allows you to retrieve the list of your Watchlists.',
            ],
            normalizationContext: [
                'groups' => [
                    'watchlist:list',
                    'domain:list',
                    'event:list',
                ],
            ],
            provider: MyWatchlistsProvider::class,
        ),
        new Get(
            openapiContext: [
                'summary' => 'Retrieve the details of a Watchlist',
                'description' => 'This endpoint allows you to retrieve the details of one of the Watchlists.',
            ],
            normalizationContext: [
                'groups' => [
                    'watchlist:item',
                    'domain:list',
                    'event:list',
                    'domain-entity:entity',
                    'nameserver-entity:nameserver',
                    'nameserver-entity:entity',
                    'tld:item',
                ],
            ],
            security: 'object.getUser() == user'
        ),
        new Get(
            routeName: 'watchlist_calendar',
            openapiContext: [
                'summary' => 'iCal feed of a Watchlist',
                'description' => 'This endpoint allows you to retrieve the iCalendar feed from a Watchlist. This feed contains events that have occurred on your monitored domain names.',
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
            openapiContext: [
                'summary' => 'Create a Watchlist',
                'description' => 'This endpoint allows you to create a new Watchlist. If the entered domain names are unknown or do not exist, an initial RDAP query will be performed on each of these domain names to establish a baseline. In other words, if you register a long Watchlist with unknown domain names, the query may take some time.',
            ],
            normalizationContext: ['groups' => 'watchlist:list'],
            denormalizationContext: ['groups' => 'watchlist:create'],
            processor: WatchlistUpdateProcessor::class,
        ),
        new Put(
            openapiContext: [
                'summary' => 'Edit a Watchlist',
                'description' => 'This endpoint allows you to modify a Watchlist.',
            ],
            normalizationContext: ['groups' => 'watchlist:list'],
            denormalizationContext: ['groups' => ['watchlist:update']],
            security: 'object.getUser() == user',
            processor: WatchlistUpdateProcessor::class,
        ),
        new Patch(
            openapiContext: [
                'summary' => 'Edit a Watchlist',
                'description' => 'This endpoint allows you to modify a Watchlist.',
            ],
            normalizationContext: ['groups' => 'watchlist:list'],
            denormalizationContext: ['groups' => ['watchlist:update']],
            security: 'object.getUser() == user',
            processor: WatchlistUpdateProcessor::class,
        ),
        new Delete(
            openapiContext: [
                'summary' => 'Delete a Watchlist',
                'description' => 'This endpoint allows you to delete a Watchlist.',
            ],
            security: 'object.getUser() == user'
        ),
        new Get(
            routeName: 'watchlist_rss_status',
            defaults: ['_format' => 'xml'],
            openapiContext: [
                'summary' => 'RSS feed of a Watchlist (EPP status)',
                'description' => 'This experimental endpoint allows retrieving the RSS feed of a Watchlist. New entries in the feed correspond to a change in the EPP status of a domain name.',
                'responses' => [
                    '200' => [
                        'description' => 'Domain EPP status RSS feed',
                        'content' => [
                            'application/atom+xml' => [
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
            name: 'rss_status'
        ),
        new Get(
            routeName: 'watchlist_rss_events',
            defaults: ['_format' => 'xml'],
            openapiContext: [
                'summary' => 'RSS feed of a Watchlist (events)',
                'description' => 'This experimental endpoint allows retrieving the RSS feed of a Watchlist. New entries in the feed correspond to a change in events for a domain name.',

                'responses' => [
                    '200' => [
                        'description' => 'Domain events RSS feed',
                        'content' => [
                            'application/atom+xml' => [
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
            name: 'rss_events'
        ),
    ],
)]
class Watchlist
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'watchlists')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?User $user = null;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['watchlist:item', 'watchlist:list', 'watchlist:token'])]
    private string $token;

    /**
     * @var Collection<int, Domain>
     */
    #[ORM\ManyToMany(targetEntity: Domain::class, inversedBy: 'watchlists')]
    #[ORM\JoinTable(name: 'watchlist_domains',
        joinColumns: [new ORM\JoinColumn(name: 'watchlist_token', referencedColumnName: 'token', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'domain_ldh_name', referencedColumnName: 'ldh_name', onDelete: 'CASCADE')])]
    #[Groups(['watchlist:create', 'watchlist:list', 'watchlist:item', 'watchlist:update'])]
    private Collection $domains;

    #[ORM\ManyToOne(inversedBy: 'watchlists')]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create', 'watchlist:update'])]
    private ?Connector $connector = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create', 'watchlist:update'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['watchlist:list', 'watchlist:item'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[SerializedName('dsn')]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create', 'watchlist:update'])]
    #[Assert\Unique]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    private ?array $webhookDsn = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create', 'watchlist:update'])]
    #[Assert\Unique]
    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    private array $trackedEvents = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create', 'watchlist:update'])]
    #[Assert\Unique]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    private array $trackedEppStatus = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create', 'watchlist:update'])]
    private ?bool $enabled = null;

    public function __construct()
    {
        $this->token = Uuid::v4();
        $this->domains = new ArrayCollection();
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

    public function getTrackedEvents(): array
    {
        return $this->trackedEvents;
    }

    public function setTrackedEvents(array $trackedEvents): static
    {
        $this->trackedEvents = $trackedEvents;

        return $this;
    }

    public function getTrackedEppStatus(): array
    {
        return $this->trackedEppStatus;
    }

    public function setTrackedEppStatus(array $trackedEppStatus): static
    {
        $this->trackedEppStatus = $trackedEppStatus;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }
}
