<?php


namespace App\Controller;

use App\Service\RDAPService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class TestController extends AbstractController
{

    public function __construct(
        private readonly RDAPService $RDAPService
    )
    {

    }

    #[Route(path: '/test/register/{fqdn}', name: 'test_register_domain')]
    public function testRegisterDomain(string $fqdn): Response
    {
        $this->RDAPService->registerDomain($fqdn);
        return new Response();
    }

}