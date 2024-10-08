<?php

namespace App\Command;

use App\Message\ProcessWatchListsTrigger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:process-watchlists',
    description: 'Process watchlists and send emails if necessary',
)]
class ProcessWatchlistsCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->bus->dispatch(new ProcessWatchListsTrigger());

        $io->success('Watchlist processing triggered!');

        return Command::SUCCESS;
    }
}
