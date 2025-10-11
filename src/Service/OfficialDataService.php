<?php

namespace App\Service;

use App\Config\RegistrarStatus;
use App\Config\TldType;
use App\Entity\IcannAccreditation;
use App\Entity\RdapServer;
use App\Entity\Tld;
use App\Repository\DomainRepository;
use App\Repository\IcannAccreditationRepository;
use App\Repository\RdapServerRepository;
use App\Repository\TldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OfficialDataService
{
    /* @see https://www.iana.org/domains/root/db */
    private const ISO_TLD_EXCEPTION = ['ac', 'eu', 'uk', 'su', 'tp'];
    private const INFRA_TLD = ['arpa'];
    private const SPONSORED_TLD = [
        'aero',
        'asia',
        'cat',
        'coop',
        'edu',
        'gov',
        'int',
        'jobs',
        'mil',
        'museum',
        'post',
        'tel',
        'travel',
        'xxx',
    ];
    private const TEST_TLD = [
        'xn--kgbechtv',
        'xn--hgbk6aj7f53bba',
        'xn--0zwm56d',
        'xn--g6w251d',
        'xn--80akhbyknj4f',
        'xn--11b5bs3a9aj6g',
        'xn--jxalpdlp',
        'xn--9t4b11yi5a',
        'xn--deba0ad',
        'xn--zckzah',
        'xn--hlcj6aya9esc7a',
    ];

    private const IANA_REGISTRAR_IDS_URL = 'https://www.iana.org/assignments/registrar-ids/registrar-ids.xml';
    private const IANA_RDAP_SERVER_LIST_URL = 'https://data.iana.org/rdap/dns.json';
    private const IANA_TLD_LIST_URL = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';
    private const ICANN_GTLD_LIST_URL = 'https://www.icann.org/resources/registries/gtlds/v2/gtlds.json';

    public const DOMAIN_DOT = '.';

    public function __construct(private HttpClientInterface $client,
        private readonly DomainRepository $domainRepository,
        private readonly RdapServerRepository $rdapServerRepository,
        private readonly TldRepository $tldRepository,
        private readonly IcannAccreditationRepository $icannAccreditationRepository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function updateRDAPServersFromIANA(): void
    {
        $this->logger->info('Start of update the RDAP server list from IANA');

        $dnsRoot = $this->client->request(
            'GET', self::IANA_RDAP_SERVER_LIST_URL
        )->toArray();

        $this->updateRDAPServers($dnsRoot);
    }

    /**
     * @throws \Exception
     */
    private function updateRDAPServers(array $dnsRoot): void
    {
        foreach ($dnsRoot['services'] as $service) {
            foreach ($service[0] as $tld) {
                if (self::DOMAIN_DOT === $tld && null === $this->tldRepository->findOneBy(['tld' => $tld])) {
                    $this->em->persist((new Tld())->setTld(self::DOMAIN_DOT)->setType(TldType::root));
                    $this->em->flush();
                }

                $tldEntity = $this->tldRepository->findOneBy(['tld' => $tld]);
                if (null === $tldEntity) {
                    $tldEntity = (new Tld())->setTld($tld)->setType(TldType::gTLD);
                    $this->em->persist($tldEntity);
                }

                foreach ($service[1] as $rdapServerUrl) {
                    $server = $this->rdapServerRepository->findOneBy(['tld' => $tldEntity->getTld(), 'url' => $rdapServerUrl]);

                    if (null === $server) {
                        $server = new RdapServer();
                    }

                    $server
                        ->setTld($tldEntity)
                        ->setUrl($rdapServerUrl)
                        ->setUpdatedAt(new \DateTimeImmutable($dnsRoot['publication'] ?? 'now'));

                    $this->em->persist($server);
                }
            }
        }
        $this->em->flush();
    }

    /**
     * @throws \Exception
     */
    public function updateRDAPServersFromFile(string $fileName): void
    {
        if (!file_exists($fileName)) {
            return;
        }

        $this->logger->info('Start of update the RDAP server list from custom config file');
        $this->updateRDAPServers(Yaml::parseFile($fileName));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateTldListIANA(): void
    {
        $this->logger->info('Start of retrieval of the list of TLDs according to IANA');
        $tldList = array_map(
            fn ($tld) => strtolower($tld),
            explode(PHP_EOL,
                $this->client->request(
                    'GET', self::IANA_TLD_LIST_URL
                )->getContent()
            ));
        array_shift($tldList);

        foreach ($tldList as $tld) {
            if ('' === $tld) {
                continue;
            }

            $this->tldRepository->createQueryBuilder('t')
                ->update()
                ->set('t.deletedAt', 'COALESCE(t.removalDate, CURRENT_TIMESTAMP())')
                ->where('t.tld != :tld')
                ->setParameter('tld', self::DOMAIN_DOT)
                ->getQuery()->execute();

            $tldEntity = $this->tldRepository->findOneBy(['tld' => $tld]);

            if (null === $tldEntity) {
                $tldEntity = new Tld();
                $tldEntity->setTld($tld);

                $this->logger->notice('New TLD detected according to IANA', [
                    'tld' => $tld,
                ]);
            }

            $type = $this->getTldType($tld);

            if (null !== $type) {
                $tldEntity->setType($type);
            } elseif (null === $tldEntity->isContractTerminated()) { // ICANN managed, must be a ccTLD
                $tldEntity->setType(TldType::ccTLD);
            } else {
                $tldEntity->setType(TldType::gTLD);
            }

            $tldEntity->setDeletedAt(null);
            $this->em->persist($tldEntity);
        }
        $this->em->flush();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function updateRegistrarListIANA(): void
    {
        $this->logger->info('Start of retrieval of the list of Registrar IDs according to IANA');
        $registrarList = $this->client->request(
            'GET', self::IANA_REGISTRAR_IDS_URL
        );

        $data = new \SimpleXMLElement($registrarList->getContent());

        foreach ($data->registry->record as $registrar) {
            $icannAcreditation = $this->icannAccreditationRepository->findOneBy(['id' => (int) $registrar->value]);
            if (null === $icannAcreditation) {
                $icannAcreditation = new IcannAccreditation();
            }

            $icannAcreditation
                ->setId((int) $registrar->value)
                ->setRegistrarName($registrar->name)
                ->setStatus(RegistrarStatus::from($registrar->status))
                ->setRdapBaseUrl($registrar->rdapurl->count() ? ($registrar->rdapurl->server) : null)
                ->setUpdated(null !== $registrar->attributes()->updated ? new \DateTimeImmutable($registrar->attributes()->updated) : null)
                ->setDate(null !== $registrar->attributes()->date ? new \DateTimeImmutable($registrar->attributes()->date) : null);

            $this->em->persist($icannAcreditation);
        }
        $this->em->flush();
    }

    private function getTldType(string $tld): ?TldType
    {
        if (in_array(strtolower($tld), self::ISO_TLD_EXCEPTION)) {
            return TldType::ccTLD;
        }
        if (in_array(strtolower($tld), self::INFRA_TLD)) {
            return TldType::iTLD;
        }
        if (in_array(strtolower($tld), self::SPONSORED_TLD)) {
            return TldType::sTLD;
        }
        if (in_array(strtolower($tld), self::TEST_TLD)) {
            return TldType::tTLD;
        }

        return null;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function updateGTldListICANN(): void
    {
        $this->logger->info('Start of retrieval of the list of gTLDs according to ICANN');

        $gTldList = $this->client->request(
            'GET', self::ICANN_GTLD_LIST_URL
        )->toArray()['gTLDs'];

        foreach ($gTldList as $gTld) {
            if ('' === $gTld['gTLD']) {
                continue;
            }
            /** @var Tld|null $gtTldEntity */
            $gtTldEntity = $this->tldRepository->findOneBy(['tld' => $gTld['gTLD']]);

            if (null === $gtTldEntity) {
                $gtTldEntity = new Tld();
                $gtTldEntity->setTld($gTld['gTLD'])->setType(TldType::gTLD);
                $this->logger->notice('New gTLD detected according to ICANN', [
                    'tld' => $gTld['gTLD'],
                ]);
            }

            $gtTldEntity
                ->setContractTerminated($gTld['contractTerminated'])
                ->setRegistryOperator($gTld['registryOperator'])
                ->setSpecification13($gTld['specification13']);
            // NOTICE: sTLDs are listed in ICANN's gTLD list

            if (null !== $gTld['removalDate']) {
                $gtTldEntity->setRemovalDate(new \DateTimeImmutable($gTld['removalDate']));
            }
            if (null !== $gTld['delegationDate']) {
                $gtTldEntity->setDelegationDate(new \DateTimeImmutable($gTld['delegationDate']));
            }
            if (null !== $gTld['dateOfContractSignature']) {
                $gtTldEntity->setDateOfContractSignature(new \DateTimeImmutable($gTld['dateOfContractSignature']));
            }
            $this->em->persist($gtTldEntity);
        }

        $this->em->flush();
    }

    public function updateDomainsWhenTldIsDeleted(): void
    {
        $this->domainRepository->createQueryBuilder('d')
            ->update()
            ->set('d.deleted', ':deleted')
            ->where('d.tld IN (SELECT t FROM '.Tld::class.' t WHERE t.deletedAt IS NOT NULL)')
            ->setParameter('deleted', true)
            ->getQuery()->execute();
    }
}
