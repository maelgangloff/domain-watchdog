<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Config\ConnectorProvider;
use App\Entity\Connector;
use App\Service\Provider\EppClientProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

readonly class ConnectorDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private KernelInterface $kernel,
    ) {
    }

    /**
     * @param Connector $data
     *
     * @return Connector
     *
     * @throws \Throwable
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        foreach ($data->getWatchlists()->getIterator() as $watchlist) {
            $watchlist->setConnector(null);
        }

        $provider = $data->getProvider();

        if (null === $provider) {
            throw new BadRequestHttpException('Provider not found');
        }

        if (ConnectorProvider::EPP === $provider) {
            (new Filesystem())->remove(EppClientProvider::buildEppCertificateFolder($this->kernel->getProjectDir(), $data->getId()));
        }

        $this->removeProcessor->process($data, $operation, $uriVariables, $context);

        return $data;
    }
}
