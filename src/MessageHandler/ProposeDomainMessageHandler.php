<?php

namespace App\MessageHandler;

use App\Message\ProposeDomainMessage;
use App\Service\RDAPService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final class ProposeDomainMessageHandler
{
    public function __construct(
        private RDAPService $RDAPService,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     */
    public function __invoke(ProposeDomainMessage $message): void
    {
        $this->RDAPService->updateDomain($message->ldhName);
    }
}
