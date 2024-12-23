<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

readonly class StatService
{
    public function __construct(
        private CacheItemPoolInterface $pool,
    ) {
    }

    public function incrementStat(string $key): bool
    {
        try {
            $item = $this->pool->getItem($key);
            $item->set(($item->get() ?? 0) + 1);

            return $this->pool->save($item);
        } catch (\Throwable) {
            // TODO: Add a retry mechanism if writing fails
        }

        return false;
    }
}
