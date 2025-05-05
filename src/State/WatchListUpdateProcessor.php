<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Domain;
use App\Entity\WatchList;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class WatchListUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly DomainRepository   $domainRepository,
        private readonly RDAPService        $RDAPService,
        private readonly KernelInterface    $kernel,
        private readonly Security           $security,
        private readonly RateLimiterFactory $rdapRequestsLimiter,
        private readonly ParameterBagInterface $parameterBag,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
    )
    {}

    /**
     * @param WatchList $data
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return WatchList
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        foreach ($data->getDomains() as $ldhName) {
            /** @var ?Domain $domain */
            $domain = $this->domainRepository->findOneBy(['ldhName' => $ldhName]);

            if (null === $domain) {
                $domain = $this->RDAPService->registerDomain($ldhName);

                if (false === $this->kernel->isDebug() && true === $this->parameterBag->get('limited_features')) {
                    $limiter = $this->rdapRequestsLimiter->create($this->security->getUser()->getUserIdentifier());
                    $limit = $limiter->consume();

                    if (!$limit->isAccepted()) {
                        throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
                    }
                }
            }

            $data->addDomain($domain);
        }

        if ($operation instanceof Post) {
            $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        return $data;
    }
}
