<?php

namespace App\Command;

use App\Entity\DomainEntity;
use App\Repository\EntityRepository;
use App\Service\RDAPService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:entity:privacy-protection',
    description: 'Enable or disable privacy protection for an entity',
)]
class EntityPrivacyCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntityRepository $entityRepository,
        private readonly RDAPService $RDAPService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('handle', InputArgument::REQUIRED, 'Entity handle')
            ->addArgument('tld', InputArgument::REQUIRED, 'Entity TLD')
            ->addOption('enable', 'ep', InputOption::VALUE_NEGATABLE, 'Enable or disable privacy protection', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entity = $this->entityRepository->findOneBy(['handle' => $input->getArgument('handle'), 'tld' => $input->getArgument('tld')]);
        $enable = $input->getOption('enable');

        if (null === $entity) {
            $io->error('Entity not found in the database.');

            return Command::FAILURE;
        }

        if ($entity->isPrivacyProtection() && $enable || !$entity->isPrivacyProtection() && !$enable) {
            $io->warning('Privacy protection is already in the requested state.');

            return Command::INVALID;
        }

        $entity->setPrivacyProtection($enable);

        if ($enable) {
            $entity->setJCard([]);
            $entity->setRemarks([]);
        } else {
            $domainEntity = $entity->getDomainEntities()->findFirst(fn (int $key, DomainEntity $de) => null === $de->getDeletedAt() && !$de->getDomain()->getDeleted());
            if (null !== $domainEntity) {
                try {
                    $this->RDAPService->registerDomain($domainEntity->getDomain()->getLdhName());
                } catch (\Exception) {
                    $io->warning('Failed to update the jCard using a linked domain.');
                }
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            'Privacy protection %s for entity %s (%s).',
            $enable ? 'enabled' : 'disabled',
            $entity->getHandle(),
            $entity->getTld()->getTld()
        ));

        return Command::SUCCESS;
    }
}
