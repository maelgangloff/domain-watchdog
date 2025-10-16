<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Exception\MalformedDomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DomainTest extends TestCase
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

    /**
     * @throws \Exception
     */
    public function testGetExpiresInDays(): void
    {
        $this->assertNull(
            (new Domain())
                ->setDeleted(true)
                ->getExpiresInDays(),
            'No guess if the domain is flagged as deleted'
        );

        $this->assertEquals(
            90, // Expiration date (10 days) + Auto Renew Period (45 days) + Redemption Period (30 days) + Pending Delete (5 days)
            (new Domain())
                ->addEvent(
                    (new DomainEvent())
                        ->setDate((new \DateTimeImmutable())->add(new \DateInterval('P10D')))
                        ->setAction('expiration')
                        ->setDeleted(false)
                )->getExpiresInDays(),
            'Guess based on domain events date'
        );

        $this->assertEquals(
            5, // Pending Delete (5 days)
            (new Domain())
                ->setStatus(['pending delete'])
                ->addDomainStatus(
                    (new DomainStatus())
                        ->setAddStatus(['pending delete'])
                        ->setDeleteStatus(['active'])
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setDate(new \DateTimeImmutable())
                )->getExpiresInDays(),
            'Guess based on domain EPP status'
        );

        $this->assertEquals(
            35, // Redemption Period (15 days) + Pending Delete (5 days)
            (new Domain())
                ->setStatus(['redemption period'])
                ->addDomainStatus(
                    (new DomainStatus())
                        ->setAddStatus(['redemption period'])
                        ->setDeleteStatus(['active'])
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setDate(new \DateTimeImmutable())
                )->getExpiresInDays(),
            'Domain name entered in the redemption period'
        );

        $this->assertEquals(
            5, // Pending Delete (5 days)
            (new Domain())
                ->setStatus(['pending delete'])
                ->addEvent(
                    (new DomainEvent())
                        ->setDate((new \DateTimeImmutable())->sub(new \DateInterval('P10D')))
                        ->setAction('expiration')
                        ->setDeleted(false)
                )
                ->addDomainStatus(
                    (new DomainStatus())
                        ->setAddStatus(['pending delete'])
                        ->setDeleteStatus(['active'])
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setDate(new \DateTimeImmutable())
                )->getExpiresInDays(),
            'Domain name entered in the pending delete period'
        );

        $this->assertEquals(
            1,
            (new Domain())
                ->setStatus(['pending delete'])
                ->addEvent(
                    (new DomainEvent())
                        ->setDate((new \DateTimeImmutable())->sub(new \DateInterval('P'.(45 + 30 + 4).'D')))
                        ->setAction('expiration')
                        ->setDeleted(false)
                )
                ->addDomainStatus(
                    (new DomainStatus())
                        ->setAddStatus(['pending delete'])
                        ->setDeleteStatus(['active'])
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setDate(new \DateTimeImmutable())
                )->getExpiresInDays(),
            'Guess based on domain status in priority'
        );

        $this->assertNull(
            (new Domain())->setStatus(['pending delete'])->getExpiresInDays(),
            'Not enough data to guess'
        );

        $this->assertEquals(
            0,
            (new Domain())
                ->setStatus(['pending delete'])
                ->addEvent(
                    (new DomainEvent())
                        ->setDate(new \DateTimeImmutable())
                        ->setAction('deletion')
                        ->setDeleted(false)
                )->getExpiresInDays(),
            'deletion event on last day (AFNIC)'
        );
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
        $mock = $this->getMockBuilder(Domain::class)
            ->onlyMethods(['getUpdatedAt', 'getDeleted', 'getExpiresInDays', 'isToBeWatchClosely', 'getStatus'])
            ->getMock();

        $mock->method('getUpdatedAt')->willReturn($updatedAt);
        $mock->method('getDeleted')->willReturn($deleted);
        $mock->method('getExpiresInDays')->willReturn($expiresIn);
        $mock->method('isToBeWatchClosely')->willReturn($watchClosely);
        $mock->method('getStatus')->willReturn($status);

        $result = $mock->isToBeUpdated($fromUser, $intensifyLastDay);

        $this->assertEquals($expected, $result);
    }
}
