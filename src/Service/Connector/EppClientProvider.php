<?php

namespace App\Service\Connector;

use App\Dto\Connector\DefaultProviderDto;
use App\Dto\Connector\EppClientProviderDto;
use App\Entity\Domain;
use Metaregistrar\EPP\eppCheckContactRequest;
use Metaregistrar\EPP\eppCheckContactResponse;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EppClientProvider extends AbstractProvider implements CheckDomainProviderInterface
{
    protected string $dtoClass = EppClientProviderDto::class;

    /** @var EppClientProviderDto */
    protected DefaultProviderDto $authData;

    private ?eppConnection $eppClient = null;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        DenormalizerInterface&NormalizerInterface $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($cacheItemPool, $serializer, $validator);
    }

    protected function assertAuthentication(): void
    {
        $this->connect();
        $this->eppClient->login();

        $this->eppClient->request(new eppHelloRequest());

        $contacts = [new eppContactHandle($this->authData->domain->registrant, eppContactHandle::CONTACT_TYPE_REGISTRANT)];
        foreach ($this->authData->domain->contacts as $role => $roid) {
            $contacts[] = new eppContactHandle($roid, $role);
        }

        /** @var eppCheckContactResponse $resp */
        $resp = $this->eppClient->request(new eppCheckContactRequest($contacts));
        foreach ($resp->getCheckedContacts() as $contact => $available) {
            if ($available) {
                throw new BadRequestHttpException("At least one of the entered contacts cannot be used because it is indicated as available ($contact).");
            }
        }

        $this->eppClient->logout();
        $this->eppClient->disconnect();
    }

    /**
     * @throws eppException
     */
    public function orderDomain(Domain $domain, bool $dryRun): void
    {
        $this->connect();

        $d = new eppDomain($domain->getLdhName());
        $d->setRegistrant($this->authData->domain->registrant);
        $d->setPeriodUnit($this->authData->domain->unit);
        $d->setPeriod($this->authData->domain->period);
        $d->setAuthorisationCode($this->authData->domain->password);

        foreach ($this->authData->domain->contacts as $type => $contact) {
            $d->addContact(new eppContactHandle($contact, $type));
        }

        if (!$dryRun) {
            $this->eppClient->request(new eppCreateDomainRequest($d));
        }

        $this->eppClient->logout();
        $this->disconnect();
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
     * @return string[]
     *
     * @throws eppException
     */
    public function checkDomains(string ...$domains): array
    {
        $this->connect();
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
     * @throws ExceptionInterface
     */
    private function connect(): void
    {
        if ($this->eppClient && $this->eppClient->isConnected()) {
            return;
        }

        $conn = new eppConnection(false, null);
        $conn->setHostname($this->authData->hostname);
        $conn->setVersion($this->authData->version);
        $conn->setLanguage($this->authData->language);
        $conn->setPort($this->authData->port);

        $conn->setUsername($this->authData->auth->username);
        $conn->setPassword($this->authData->auth->password);

        $ssl = (array) $this->serializer->normalize($this->authData->auth->ssl, 'json');

        if (isset($this->authData->file_certificate_pem, $this->authData->file_certificate_key)) {
            $conn->setSslContext(stream_context_create(['ssl' => [
                ...$ssl,
                'local_cert' => $this->authData->file_certificate_pem,
                'local_pk' => $this->authData->file_certificate_key,
            ]]));
        } else {
            unset($ssl['local_cert'], $ssl['local_pk']);
            $conn->setSslContext(stream_context_create(['ssl' => $ssl]));
        }

        $conn->setExtensions($this->authData->extURI);
        $conn->setServices($this->authData->objURI);

        $conn->connect();
        $this->eppClient = $conn;
    }

    private function disconnect(): void
    {
        $this->eppClient->disconnect();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public static function buildEppCertificateFolder(string $projectDir, string $connectorId): string
    {
        return sprintf('%s/%s/%s/', $projectDir, 'var/epp-certificates', $connectorId);
    }
}
