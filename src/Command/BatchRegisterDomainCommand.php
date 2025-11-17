<?php

namespace App\Command;

use App\Message\UpdateDomain;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsCommand(
    name: 'app:batch-register-domains',
    description: 'Register a domain list',
)]
class BatchRegisterDomainCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to a file containing a list of domain names')
            ->addOption('only-new', 'on', InputOption::VALUE_NEGATABLE, 'Do not update domain names if they are already in the database', false)
        ;
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');
        $onlyNew = (bool) $input->getOption('only-new');

        if (!file_exists($file) || !is_readable($file)) {
            $io->error(sprintf('File "%s" does not exist or is not readable.', $file));

            return Command::FAILURE;
        }
        $domains = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($domains)) {
            $io->warning('The list is empty.');

            return Command::SUCCESS;
        }

        $io->title('Registering domains');
        /** @var string $ldhName */
        foreach ($domains as $ldhName) {
            $this->messageBus->dispatch(new UpdateDomain($ldhName, null, $onlyNew), [
                new TransportNamesStamp('rdap_low'),
            ]);
        }

        $io->success(sprintf('Imported %d domain names.', count($domains)));

        return Command::SUCCESS;
    }
}
