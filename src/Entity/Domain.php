<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Config\EventAction;
use App\Exception\MalformedDomainException;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use App\State\AutoRegisterDomainProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[ApiResource(
    operations: [
        /*
        new GetCollection(
            normalizationContext: [
                'groups' => [
                    'domain:list'
                ]
            ]
        ),
        */
        new Get(
            uriTemplate: '/domains/{ldhName}', // Do not delete this line, otherwise Symfony interprets the TLD of the domain name as a return type
            normalizationContext: [
                'groups' => [
                    'domain:item',
                    'event:list',
                    'domain-entity:entity',
                    'nameserver-entity:nameserver',
                    'nameserver-entity:entity',
                    'tld:item',
                    'ds:list',
                ],
            ],
        ),
    ],
    provider: AutoRegisterDomainProvider::class
)]
class Domain
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:item', 'watchlist:list'])]
    private ?string $ldhName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:item'])]
    private ?string $handle = null;

    /**
     * @var Collection<int, DomainEvent>
     */
    #[ORM\OneToMany(targetEntity: DomainEvent::class, mappedBy: 'domain', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:list'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
        ]
    )]
    private Collection $events;

    /**
     * @var Collection<int, DomainEntity>
     */
    #[ORM\OneToMany(targetEntity: DomainEntity::class, mappedBy: 'domain', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['domain:item', 'watchlist:item'])]
    #[SerializedName('entities')]
    private Collection $domainEntities;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:item', 'watchlist:list'])]
    private array $status = [];

    /**
     * @var Collection<int, WatchList>
     */
    #[ORM\ManyToMany(targetEntity: WatchList::class, mappedBy: 'domains', cascade: ['persist'])]
    private Collection $watchLists;

    /**
     * @var Collection<int, Nameserver>
     */
    #[ORM\ManyToMany(targetEntity: Nameserver::class, inversedBy: 'domains', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'domain_nameservers',
        joinColumns: [new ORM\JoinColumn(name: 'domain_ldh_name', referencedColumnName: 'ldh_name')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'nameserver_ldh_name', referencedColumnName: 'ldh_name')]
    )]
    #[Groups(['domain:item', 'watchlist:item'])]
    private Collection $nameservers;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['domain:item', 'domain:list'])]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'tld', nullable: false)]
    #[Groups(['domain:item', 'domain:list'])]
    private ?Tld $tld = null;

    #[ORM\Column(nullable: false)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:item', 'watchlist:list'])]
    private ?bool $deleted;

    #[Groups(['domain:item'])]
    private ?RdapServer $rdapServer;

    /**
     * @var Collection<int, DomainStatus>
     */
    #[ORM\OneToMany(targetEntity: DomainStatus::class, mappedBy: 'domain', orphanRemoval: true)]
    #[Groups(['domain:item'])]
    #[SerializedName('oldStatus')]
    private Collection $domainStatuses;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    #[Groups(['domain:item', 'domain:list'])]
    private ?bool $delegationSigned = null;

    /**
     * @var Collection<int, DnsKey>
     */
    #[ORM\OneToMany(targetEntity: DnsKey::class, mappedBy: 'domain', orphanRemoval: true)]
    #[Groups(['domain:item'])]
    private Collection $dnsKey;

    private const IMPORTANT_EVENTS = [EventAction::Deletion->value, EventAction::Expiration->value];
    private const IMPORTANT_STATUS = [
        'redemption period',
        'pending delete',
        'pending create',
        'pending renew',
        'pending restore',
        'pending transfer',
        'pending update',
        'add period',
    ];

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->domainEntities = new ArrayCollection();
        $this->watchLists = new ArrayCollection();
        $this->nameservers = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable('now');
        $this->createdAt = $this->updatedAt;
        $this->deleted = false;
        $this->domainStatuses = new ArrayCollection();
        $this->dnsKey = new ArrayCollection();
    }

    public function getLdhName(): ?string
    {
        return $this->ldhName;
    }

    /**
     * @throws MalformedDomainException
     */
    public function setLdhName(string $ldhName): static
    {
        $this->ldhName = RDAPService::convertToIdn($ldhName);

        return $this;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): static
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * @return Collection<int, DomainEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(DomainEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setDomain($this);
        }

        return $this;
    }

    public function removeEvent(DomainEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getDomain() === $this) {
                $event->setDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DomainEntity>
     */
    public function getDomainEntities(): Collection
    {
        return $this->domainEntities;
    }

    public function addDomainEntity(DomainEntity $domainEntity): static
    {
        if (!$this->domainEntities->contains($domainEntity)) {
            $this->domainEntities->add($domainEntity);
            $domainEntity->setDomain($this);
        }

        return $this;
    }

    public function removeDomainEntity(DomainEntity $domainEntity): static
    {
        if ($this->domainEntities->removeElement($domainEntity)) {
            // set the owning side to null (unless already changed)
            if ($domainEntity->getDomain() === $this) {
                $domainEntity->setDomain(null);
            }
        }

        return $this;
    }

    public function getStatus(): array
    {
        return $this->status;
    }

    public function setStatus(array $status): static
    {
        $this->status = $status;

        return $this;
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
            $watchList->addDomain($this);
        }

        return $this;
    }

    public function removeWatchList(WatchList $watchList): static
    {
        if ($this->watchLists->removeElement($watchList)) {
            $watchList->removeDomain($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Nameserver>
     */
    public function getNameservers(): Collection
    {
        return $this->nameservers;
    }

    public function addNameserver(Nameserver $nameserver): static
    {
        if (!$this->nameservers->contains($nameserver)) {
            $this->nameservers->add($nameserver);
        }

        return $this;
    }

    public function removeNameserver(Nameserver $nameserver): static
    {
        $this->nameservers->removeElement($nameserver);

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): static
    {
        $this->setUpdatedAt(new \DateTimeImmutable('now'));
        if (null === $this->getCreatedAt()) {
            $this->setCreatedAt($this->getUpdatedAt());
        }

        return $this;
    }

    private function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getTld(): ?Tld
    {
        return $this->tld;
    }

    public function setTld(?Tld $tld): static
    {
        $this->tld = $tld;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Determines if a domain name needs special attention.
     * These domain names are those whose last event was expiration or deletion.
     *
     * @throws \Exception
     */
    protected function isToBeWatchClosely(): bool
    {
        $status = $this->getStatus();
        if ((!empty($status) && count(array_intersect($status, self::IMPORTANT_STATUS))) || $this->getDeleted()) {
            return true;
        }

        /** @var DomainEvent[] $events */
        $events = $this->getEvents()
            ->filter(fn (DomainEvent $e) => !$e->getDeleted() && $e->getDate() <= new \DateTimeImmutable('now'))
            ->toArray();

        usort($events, fn (DomainEvent $e1, DomainEvent $e2) => $e2->getDate() <=> $e1->getDate());

        return !empty($events) && in_array($events[0]->getAction(), self::IMPORTANT_EVENTS);
    }

    /**
     * Returns true if one or more of these conditions are met:
     * - It has been more than 7 days since the domain name was last updated
     * - It has been more than 12 minutes and the domain name has statuses that suggest it is not stable
     * - It has been more than 1 day and the domain name is blocked in DNS
     *
     * @throws \Exception
     */
    public function isToBeUpdated(bool $fromUser = true, bool $intensifyLastDay = false): bool
    {
        $updatedAtDiff = $this->getUpdatedAt()->diff(new \DateTimeImmutable());

        if ($updatedAtDiff->days >= 7) {
            return true;
        }

        if ($this->getDeleted()) {
            return $fromUser;
        }

        $expiresIn = $this->getExpiresInDays();

        if ($intensifyLastDay && (0 === $expiresIn || 1 === $expiresIn)) {
            return true;
        }

        $minutesDiff = $updatedAtDiff->h * 60 + $updatedAtDiff->i;
        if (($minutesDiff >= 12 || $fromUser) && $this->isToBeWatchClosely()) {
            return true;
        }

        if (
            count(array_intersect($this->getStatus(), ['auto renew period', 'client hold', 'server hold'])) > 0
            && $updatedAtDiff->days >= 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return Collection<int, DomainStatus>
     */
    public function getDomainStatuses(): Collection
    {
        return $this->domainStatuses;
    }

    public function addDomainStatus(DomainStatus $domainStatus): static
    {
        if (!$this->domainStatuses->contains($domainStatus)) {
            $this->domainStatuses->add($domainStatus);
            $domainStatus->setDomain($this);
        }

        return $this;
    }

    public function removeDomainStatus(DomainStatus $domainStatus): static
    {
        if ($this->domainStatuses->removeElement($domainStatus)) {
            // set the owning side to null (unless already changed)
            if ($domainStatus->getDomain() === $this) {
                $domainStatus->setDomain(null);
            }
        }

        return $this;
    }

    public function getRdapServer(): ?RdapServer
    {
        return $this->rdapServer;
    }

    public function setRdapServer(?RdapServer $rdapServer): static
    {
        $this->rdapServer = $rdapServer;

        return $this;
    }

    public function isDelegationSigned(): ?bool
    {
        return $this->delegationSigned;
    }

    public function setDelegationSigned(bool $delegationSigned): static
    {
        $this->delegationSigned = $delegationSigned;

        return $this;
    }

    public function isRedemptionPeriod(): bool
    {
        return in_array('redemption period', $this->getStatus());
    }

    public function isPendingDelete(): bool
    {
        return in_array('pending delete', $this->getStatus()) && !in_array('redemption period', $this->getStatus());
    }

    /**
     * @throws \DateMalformedIntervalStringException
     */
    private function calculateDaysFromStatus(\DateTimeImmutable $now): ?int
    {
        $lastStatus = $this->getDomainStatuses()->last();
        if (false === $lastStatus) {
            return null;
        }

        if ($this->isPendingDelete() && (
            in_array('pending delete', $lastStatus->getAddStatus())
            || in_array('redemption period', $lastStatus->getDeleteStatus()))
        ) {
            return self::daysBetween($now, $lastStatus->getCreatedAt()->add(new \DateInterval('P'. 5 .'D')));
        }

        if ($this->isRedemptionPeriod()
            && in_array('redemption period', $lastStatus->getAddStatus())
        ) {
            return self::daysBetween($now, $lastStatus->getCreatedAt()->add(new \DateInterval('P'.(30 + 5).'D')));
        }

        return null;
    }

    /*
    private function calculateDaysFromEvents(\DateTimeImmutable $now): ?int
    {
        $lastChangedEvent = $this->getEvents()->findFirst(fn (int $i, DomainEvent $e) => !$e->getDeleted() && EventAction::LastChanged->value === $e->getAction());
        if (null === $lastChangedEvent) {
            return null;
        }

        if ($this->isRedemptionPeriod()) {
            return self::daysBetween($now, $lastChangedEvent->getDate()->add(new \DateInterval('P'.(30 + 5).'D')));
        }
        if ($this->isPendingDelete()) {
            return self::daysBetween($now, $lastChangedEvent->getDate()->add(new \DateInterval('P'. 5 .'D')));
        }

        return null;
    }
    */

    private static function daysBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        $interval = $start->setTime(0, 0)->diff($end->setTime(0, 0));

        return $interval->invert ? -$interval->days : $interval->days;
    }

    private static function returnExpiresIn(array $guesses): ?int
    {
        $filteredGuesses = array_filter($guesses, function ($value) {
            return null !== $value;
        });

        if (empty($filteredGuesses)) {
            return null;
        }

        return max(min($filteredGuesses), 0);
    }

    /**
     * @throws \Exception
     */
    private function getRelevantDates(): array
    {
        $expiredAt = $deletedAt = null;
        foreach ($this->getEvents()->getIterator() as $event) {
            if (!$event->getDeleted()) {
                if ('expiration' === $event->getAction()) {
                    $expiredAt = $event->getDate();
                } elseif ('deletion' === $event->getAction()) {
                    $deletedAt = $event->getDate();
                }
            }
        }

        return [$expiredAt, $deletedAt];
    }

    /**
     * @throws \Exception
     */
    #[Groups(['domain:item', 'domain:list'])]
    public function getExpiresInDays(): ?int
    {
        if ($this->getDeleted()) {
            return null;
        }

        $now = new \DateTimeImmutable();
        [$expiredAt, $deletedAt] = $this->getRelevantDates();

        if ($expiredAt) {
            $guess = self::daysBetween($now, $expiredAt->add(new \DateInterval('P'.(45 + 30 + 5).'D')));
        }

        if ($deletedAt) {
            // It has been observed that AFNIC, on the last day, adds a "deleted" event and removes the redemption period status.
            if (0 === self::daysBetween($now, $deletedAt) && $this->isPendingDelete()) {
                return 0;
            }

            $guess = self::daysBetween($now, $deletedAt->add(new \DateInterval('P'. 30 .'D')));
        }

        return self::returnExpiresIn([
            $guess ?? null,
            $this->calculateDaysFromStatus($now),
        ]);
    }

    /**
     * @return Collection<int, DnsKey>
     */
    public function getDnsKey(): Collection
    {
        return $this->dnsKey;
    }

    public function addDnsKey(DnsKey $dnsKey): static
    {
        if (!$this->dnsKey->contains($dnsKey)) {
            $this->dnsKey->add($dnsKey);
            $dnsKey->setDomain($this);
        }

        return $this;
    }

    public function removeDnsKey(DnsKey $dnsKey): static
    {
        if ($this->dnsKey->removeElement($dnsKey)) {
            // set the owning side to null (unless already changed)
            if ($dnsKey->getDomain() === $this) {
                $dnsKey->setDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Event[]
     *
     * @throws ParseException
     * @throws EofException
     * @throws InvalidDataException
     * @throws \Exception
     */
    public function getDomainCalendarEvents(): array
    {
        $events = [];
        $attendees = [];

        /* @var DomainEntity $entity */
        foreach ($this->getDomainEntities()->filter(fn (DomainEntity $domainEntity) => !$domainEntity->getDeletedAt())->getIterator() as $domainEntity) {
            $jCard = $domainEntity->getEntity()->getJCard();

            if (empty($jCard)) {
                continue;
            }

            $vCardData = Reader::readJson($jCard);

            if (empty($vCardData->EMAIL) || empty($vCardData->FN)) {
                continue;
            }

            $email = (string) $vCardData->EMAIL;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $attendees[] = (new Attendee(new EmailAddress($email)))->setDisplayName((string) $vCardData->FN);
        }

        /** @var DomainEvent $event */
        foreach ($this->getEvents()->filter(fn (DomainEvent $e) => $e->getDate()->diff(new \DateTimeImmutable('now'))->y <= 10)->getIterator() as $event) {
            $events[] = (new Event())
                ->setLastModified(new Timestamp($this->getUpdatedAt()))
                ->setStatus(EventStatus::CONFIRMED())
                ->setSummary($this->getLdhName().': '.$event->getAction())
                ->addCategory(new Category($event->getAction()))
                ->setAttendees($attendees)
                ->setOccurrence(new SingleDay(new Date($event->getDate()))
                );
        }

        $expiresInDays = $this->getExpiresInDays();

        if (null !== $expiresInDays) {
            $events[] = (new Event())
                ->setLastModified(new Timestamp($this->getUpdatedAt()))
                ->setStatus(EventStatus::CONFIRMED())
                ->setSummary($this->getLdhName().': estimated WHOIS release date')
                ->addCategory(new Category('release'))
                ->setAttendees($attendees)
                ->setOccurrence(new SingleDay(new Date(
                    (new \DateTimeImmutable())->setTime(0, 0)->add(new \DateInterval('P'.$expiresInDays.'D'))
                ))
                );
        }

        return $events;
    }
}
