<?php

namespace App\MessageHandler;

use App\Message\UpdateRdapServers;
use App\Service\RDAPService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateRdapServersHandler
{

    public function __construct(private RDAPService $RDAPService)
    {

    }

    public function __invoke(UpdateRdapServers $message): void
    {
        $this->RDAPService->updateRDAPServers();
    }
}
