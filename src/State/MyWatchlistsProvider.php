<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\WatchlistRepository;
use Symfony\Bundle\SecurityBundle\Security;

readonly class MyWatchlistsProvider implements ProviderInterface
{
    public function __construct(private Security $security, private WatchlistRepository $watchlistRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $this->watchlistRepository->fetchWatchlistsForUser($user);
    }
}
