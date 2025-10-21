<?php

namespace App\Controller;

use App\Entity\Statistics;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;

class StatisticsController extends AbstractController
{
    public function __construct(
        private readonly CacheItemPoolInterface $pool,
        private readonly DomainRepository $domainRepository,
        private readonly WatchListRepository $watchListRepository,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(): Statistics
    {
        $stats = new Statistics();

        $stats
            ->setRdapQueries($this->pool->getItem('stats.rdap_queries.count')->get() ?? 0)
            ->setDomainPurchased($this->pool->getItem('stats.domain.purchased')->get() ?? 0)
            ->setDomainPurchaseFailed($this->pool->getItem('stats.domain.purchase.failed')->get() ?? 0)
            ->setAlertSent($this->pool->getItem('stats.alert.sent')->get() ?? 0)

            ->setDomainTracked(
                $this->getCachedItem('stats.domain.tracked', fn () => $this->watchListRepository->getTrackedDomainCount())
            )
            ->setDomainCount(
                $this->getCachedItem('stats.domain.count', fn () => $this->domainRepository->getActiveDomainCountByTld())
            )
            ->setDomainCountTotal(
                $this->getCachedItem('stats.domain.total', fn () => $this->domainRepository->count(['deleted' => false])
                ));

        return $stats;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getCachedItem(string $key, callable $getItemFunction)
    {
        $item = $this->pool->getItem($key);

        if (!$item->isHit() || $this->kernel->isDebug()) {
            $value = $getItemFunction();
            $item
                ->set($value)
                ->expiresAfter(new \DateInterval('PT6H'));
            $this->pool->save($item);

            return $value;
        } else {
            return $item->get();
        }
    }
}
