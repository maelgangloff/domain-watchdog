<?php

namespace App\Command;

use App\Message\UpdateRdapServers;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:update-rdap-servers',
    description: 'Updates the RDAP servers cache (requires MQ to be running)',
)]
class UpdateRdapServersCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->bus->dispatch(new UpdateRdapServers());

        $io->success('Message sent!');

        return Command::SUCCESS;
    }
}
