<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Zenstruck\Foundry\Test\Factories;

final class MeControllerTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetMyProfile(): void
    {
        $testUser = UserFactory::createOne();
        $client = MeControllerTest::createClientWithCredentials(MeControllerTest::getToken($testUser));

        $client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }
}
