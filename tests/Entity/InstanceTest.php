<?php

namespace App\Tests\Entity;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Instance;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Zenstruck\Foundry\Test\Factories;

final class InstanceTest extends ApiTestCase
{
    use Factories;

    /**
     * @throws TransportExceptionInterface
     */
    public function testInstance(): void
    {
        $client = InstanceTest::createClient();
        $client->request('GET', '/api/config');
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Instance::class);
    }
}
