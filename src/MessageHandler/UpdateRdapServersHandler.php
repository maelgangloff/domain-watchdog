<?php

namespace App\MessageHandler;

use App\Message\UpdateRdapServers;
use App\Service\RDAPService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final readonly class UpdateRdapServersHandler
{
    public function __construct(
        private RDAPService $RDAPService,
        private ParameterBagInterface $bag,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface|\Throwable
     */
    public function __invoke(UpdateRdapServers $message): void
    {
        /** @var \Throwable[] $throws */
        $throws = [];

        /*
         * First, we update the list of TLDs from IANA because it is the official list of TLDs.
         * Then, we update from ICANN because it allows to have information about generic top-level domains (gTLDs).
         */

        try {
            $this->RDAPService->updateTldListIANA();
            $this->RDAPService->updateGTldListICANN();
        } catch (\Throwable $throwable) {
            $throws[] = $throwable;
        }

        /*
         * Finally, we take the list from IANA and import it again to allow the classification of the latest types of TLDs.
         */

        try {
            $this->RDAPService->updateRDAPServersFromIANA();
        } catch (\Throwable $throwable) {
            $throws[] = $throwable;
        }

        /*
         * If it exists, the list of custom RDAP servers is updated at this time.
         */

        try {
            $this->RDAPService->updateRDAPServersFromFile($this->bag->get('custom_rdap_servers_file'));
        } catch (\Throwable $throwable) {
            $throws[] = $throwable;
        }

        try {
            $this->RDAPService->updateRegistrarListIANA();
        } catch (\Throwable $throwable) {
            $throws[] = $throwable;
        }

        if (!empty($throws)) {
            throw $throws[0];
        }
    }
}
