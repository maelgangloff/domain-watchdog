<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DomainRefreshController extends AbstractController
{

    public function __construct(private readonly DomainRepository $domainRepository,
                                private readonly RDAPService      $RDAPService)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(string $ldhName,): ?Domain
    {
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(["ldhName" => $ldhName]);
        if ($domain === null ||
            $domain->getUpdatedAt()->diff(new DateTimeImmutable('now'))->days >= 7) {

            //TODO : Domain search rate limit here, before the RDAP request
            $domain = $this->RDAPService->registerDomain($ldhName);
        }
        return $domain;
    }
}