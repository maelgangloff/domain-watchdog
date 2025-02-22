<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Metaregistrar\EPP\eppCheckDomainRequest;
use Metaregistrar\EPP\eppCheckDomainResponse;
use Metaregistrar\EPP\eppConnection;
use Metaregistrar\EPP\eppContactHandle;
use Metaregistrar\EPP\eppCreateDomainRequest;
use Metaregistrar\EPP\eppDomain;
use Metaregistrar\EPP\eppException;
use Metaregistrar\EPP\eppHelloRequest;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class EppClientProvider extends AbstractProvider implements CheckDomainProviderInterface
{
    public const EPP_CERTIFICATES_PATH = '../var/epp-certificates/';

    private eppConnection $eppClient;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
    ) {
        parent::__construct($cacheItemPool);
    }

    protected function verifySpecificAuthData(array $authData): array
    {
        // TODO: Create DTO for each authData schema
        unset($authData['certificate_pem']);
        unset($authData['certificate_key']);

        return $authData;
    }

    protected function assertAuthentication(): void
    {
        $this->connect($this->authData);
        $this->eppClient->login();

        $this->eppClient->request(new eppHelloRequest());

        $this->eppClient->logout();
        $this->eppClient->disconnect();
    }

    /**
     * @throws eppException
     */
    public function orderDomain(Domain $domain, bool $dryRun): void
    {
        $this->connect($this->authData);

        $d = new eppDomain($domain->getLdhName());
        $d->setRegistrant($this->authData['domain']['registrant']);
        $d->setPeriodUnit($this->authData['domain']['unit']);
        $d->setPeriod($this->authData['domain']['period']);
        $d->setAuthorisationCode($this->authData['domain']['password']);

        foreach ($this->authData['domain']['contacts'] as $type => $contact) {
            $d->addContact(new eppContactHandle($contact, $type));
        }

        if (!$dryRun) {
            $this->eppClient->request(new eppCreateDomainRequest($d));
        }

        $this->eppClient->logout();
        $this->eppClient->disconnect();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.epp.supported-tld');
    }

    protected function getSupportedTldList(): array
    {
        return [];
    }

    public function isSupported(Domain ...$domainList): bool
    {
        if (0 === count($domainList)) {
            return true;
        }
        $tld = $domainList[0]->getTld();

        foreach ($domainList as $domain) {
            if ($domain->getTld() !== $tld) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws eppException
     */
    public function checkDomains(string ...$domains): array
    {
        $this->connect($this->authData);
        $this->eppClient->login();

        $check = new eppCheckDomainRequest($domains);

        /** @var eppCheckDomainResponse $response */
        $response = $this->eppClient->request($check);
        $checkedDomains = $response->getCheckedDomains();

        $return = array_map(
            fn (array $d) => $d['domainname'],
            array_filter($checkedDomains, fn (array $d) => true === $d['available'])
        );

        $this->eppClient->logout();
        $this->eppClient->disconnect();

        return $return;
    }

    /**
     * @throws eppException
     */
    private function connect(array $authData): void
    {
        $conn = new eppConnection(false, null);
        $conn->setHostname($authData['hostname']);
        $conn->setVersion($authData['version']);
        $conn->setLanguage($authData['language']);
        $conn->setPort($authData['port']);

        $conn->setUsername($authData['auth']['username']);
        $conn->setPassword($authData['auth']['password']);
        $conn->setSslContext(stream_context_create(['ssl' => [
            ...$authData['auth']['ssl'],
            'local_cert' => $authData['files']['pem'],
            'local_pk' => $authData['files']['key'],
        ]]));

        $conn->setXpathExtensions($authData['xPathURI']);
        $conn->setExtensions($authData['extURI']);
        $conn->setServices($authData['objURI']);

        $conn->connect();
        $this->eppClient = $conn;
    }
}
