<?php

namespace App\Command;

use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:register-domain',
    description: 'Register a domain name in the database',
)]
class RegisterDomainCommand extends Command
{
    public function __construct(
        private readonly DomainRepository $domainRepository,
        private readonly RDAPService $rdapService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain name to register')
            ->addOption('force', 'f', InputOption::VALUE_NEGATABLE, 'Do not check the freshness of the data and still make the query', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ldhName = strtolower(idn_to_ascii($input->getArgument('domain')));
        $force = (bool) $input->getOption('force');
        $domain = $this->domainRepository->findOneBy(['ldhName' => $ldhName]);

        try {
            if (null !== $domain && !$force) {
                if (!$domain->isToBeUpdated()) {
                    $io->warning('The domain name is already present in the database and does not need to be updated at this time.');

                    return Command::SUCCESS;
                }
            }
            $this->rdapService->registerDomain($ldhName);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('The domain name has been successfully registered in the database.');

        return Command::SUCCESS;
    }
}
