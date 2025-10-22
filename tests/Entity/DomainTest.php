<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Entity\Tld;
use App\Exception\MalformedDomainException;
use App\Service\RDAPService;
use App\Tests\Service\RDAPServiceTest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DependsExternal;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV4;

class DomainTest extends KernelTestCase
{
    public function testIsRedemptionPeriod(): void
    {
        $this->assertTrue(
            (new Domain())
                ->setStatus(['redemption period'])
                ->isRedemptionPeriod()
        );

        $this->assertFalse(
            (new Domain())
                ->setStatus(['active'])
                ->isRedemptionPeriod()
        );
    }

    public function testIsPendingDelete(): void
    {
        $this->assertTrue(
            (new Domain())
                ->setStatus(['pending delete'])
                ->isPendingDelete()
        );

        $this->assertFalse(
            (new Domain())
                ->setStatus(['active'])
                ->isPendingDelete()
        );

        $this->assertFalse(
            (new Domain())
                ->setStatus(['redemption period', 'pending delete'])
                ->isPendingDelete()
        );
    }

    #[DataProvider('domainProvider')]
    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetExpiresInDays(?int $expected, Domain $domain, string $message): void
    {
        /** @var RDAPService $RDAPService */
        $RDAPService = self::getContainer()->get(RDAPService::class);

        $this->assertEquals($expected, $RDAPService->getExpiresInDays($domain), $message);
    }

    /**
     * @throws MalformedDomainException
     * @throws ORMException
     */
    public static function domainProvider(): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $arpaTld = $entityManager->getReference(Tld::class, 'arpa');

        $now = new \DateTimeImmutable();

        // Domain deleted
        $domainDeleted = (new Domain())
            ->setLdhName((new UuidV4())->toString())
            ->setTld($arpaTld)
            ->setDeleted(true);

        $entityManager->persist($domainDeleted);

        // Domain with future expiration event
        $domainExpirationEvent = (new Domain())
            ->setLdhName((new UuidV4())->toString())
            ->setTld($arpaTld)
            ->addEvent(
                (new DomainEvent())
                    ->setDate($now->add(new \DateInterval('P10D')))
                    ->setAction('expiration')
                    ->setDeleted(false)
            );

        $entityManager->persist($domainExpirationEvent);

        // Domain with pending delete status
        $domainPendingDelete = (new Domain())
            ->setLdhName((new UuidV4())->toString())
            ->setTld($arpaTld)
            ->setStatus(['pending delete']);

        $entityManager->persist($domainPendingDelete);
        $entityManager->persist((new DomainStatus())
            ->setDomain($domainPendingDelete)
            ->setAddStatus(['pending delete'])
            ->setDeleteStatus(['active'])
            ->setCreatedAt($now)
            ->setDate($now));

        // Domain in redemption period
        $domainRedemption = (new Domain())
            ->setLdhName((new UuidV4())->toString())
            ->setTld($arpaTld)
            ->setStatus(['redemption period']);

        $entityManager->persist($domainRedemption);
        $entityManager->persist((new DomainStatus())
            ->setDomain($domainRedemption)
            ->setAddStatus(['redemption period'])
            ->setDeleteStatus(['active'])
            ->setCreatedAt($now)
            ->setDate($now));

        // Domain with deletion event today
        $domainDeletionToday = (new Domain())
            ->setLdhName((new UuidV4())->toString())
            ->setTld($arpaTld)
            ->setStatus(['pending delete'])
            ->addEvent(
                (new DomainEvent())
                    ->setDate($now)
                    ->setAction('deletion')
                    ->setDeleted(false)
            );

        $entityManager->persist($domainDeletionToday);

        // Domain with status but not enough data
        $domainNotEnoughData = (new Domain())
            ->setLdhName((new UuidV4())->toString())
            ->setTld($arpaTld)
            ->setStatus(['pending delete']);

        $entityManager->persist($domainNotEnoughData);
        $entityManager->flush();

        return [
            [null, $domainDeleted, 'No guess if the domain is flagged as deleted'],
            [90, $domainExpirationEvent, 'Guess based on domain events date'],
            [5, $domainPendingDelete, 'Guess based on domain EPP status'],
            [35, $domainRedemption, 'Domain name entered in the redemption period'],
            [0, $domainDeletionToday, 'deletion event on last day (AFNIC)'],
            [null, $domainNotEnoughData, 'Not enough data to guess'],
        ];
    }

    public function testIdnDomainName(): void
    {
        /*
         * @see https://en.wikipedia.org/wiki/IDN_Test_TLDs
         */
        $this->assertEquals('xn--zckzah',
            (new Domain())->setLdhName('テスト')->getLdhName(),
            'IDN TLD'
        );

        $this->assertEquals('xn--r8jz45g.xn--zckzah',
            (new Domain())->setLdhName('例え.テスト')->getLdhName(),
            'IDN Domain Name'
        );

        $this->assertEquals('test.xn--r8jz45g.xn--zckzah',
            (new Domain())->setLdhName('test.例え.テスト')->getLdhName(),
            'IDN FQDN'
        );
    }

    public function testInvalidDomainName()
    {
        $this->expectException(MalformedDomainException::class);
        (new Domain())->setLdhName('*');
    }

    public static function isToBeUpdatedProvider(): array
    {
        $now = new \DateTimeImmutable();

        return [
            // 1. updatedAt >= 7 days -> true
            [$now->modify('-8 days'), false, false, false, 10, false, [], true],

            // 2. deleted = true && fromUser = true -> true
            [$now->modify('-1 day'), true, true, false, 10, false, [], true],

            // 3. deleted = true && fromUser = false -> false
            [$now->modify('-1 day'), true, false, false, 10, false, [], false],

            // 4. intensifyLastDay = true && expiresIn = 0 -> true
            [$now->modify('-1 hour'), false, false, true, 0, false, [], true],

            // 5. intensifyLastDay = true && expiresIn = 1 -> true
            [$now->modify('-1 hour'), false, false, true, 1, false, [], true],

            // 6. watchClosely = true && minutesDiff >= 12 -> true
            [$now->modify('-15 minutes'), false, false, false, 5, true, [], true],

            // 7. watchClosely = true && fromUser = true (minutesDiff < 12) -> true
            [$now->modify('-1 minute'), false, true, false, 5, true, [], true],

            // 8. status = "client hold" && updatedAt >= 1 jour -> true
            [$now->modify('-2 days'), false, false, false, 10, false, ['client hold'], true],

            // 9. no cases -> false
            [$now->modify('-1 hour'), false, false, false, 10, false, [], false],
        ];
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('isToBeUpdatedProvider')]
    public function testIsToBeUpdated(
        \DateTimeImmutable $updatedAt,
        bool $deleted,
        bool $fromUser,
        bool $intensifyLastDay,
        int $expiresIn,
        bool $watchClosely,
        array $status,
        bool $expected,
    ): void {
        $rdapServiceMock = $this->getMockBuilder(RDAPService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExpiresInDays'])
            ->getMock();

        $domainMock = $this->getMockBuilder(Domain::class)
            ->onlyMethods(['getUpdatedAt', 'getDeleted', 'isToBeWatchClosely', 'getStatus'])
            ->getMock();

        $domainMock->method('getUpdatedAt')->willReturn($updatedAt);
        $domainMock->method('getDeleted')->willReturn($deleted);
        $rdapServiceMock->method('getExpiresInDays')->willReturn($expiresIn);
        $domainMock->method('isToBeWatchClosely')->willReturn($watchClosely);
        $domainMock->method('getStatus')->willReturn($status);

        $result = $rdapServiceMock->isToBeUpdated($domainMock, $fromUser, $intensifyLastDay);

        $this->assertEquals($expected, $result);
    }
}
