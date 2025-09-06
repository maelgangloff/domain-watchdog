<?php declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use DateInterval;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;

final  class DomainTest extends TestCase
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
     * @throws Exception
     */
    public function testGetExpiresInDays(): void
    {
        $this->assertNull(
            (new Domain())
                ->setDeleted(true)
                ->getExpiresInDays()
        );

        $this->assertEquals(
            90, // Expiration date (10 days) + Auto Renew Period (45 days) + Redemption Period (30 days) + Pending Delete Period (10 days)
            (new Domain())
                ->addEvent(
                    (new DomainEvent())
                        ->setDate((new DateTimeImmutable())->add(new DateInterval('P10D')))
                        ->setAction('expiration')
                        ->setDeleted(false)
                )->getExpiresInDays()
        );


    }
}