<?php

namespace App\Controller;

use App\Service\RDAPService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DomainRefreshController extends AbstractController
{

    /**
     * @throws Exception
     */
    public function __invoke(string $ldhName, RDAPService $RDAPService): void
    {
        $RDAPService->registerDomains([$ldhName]);
    }
}