<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\AbstractTest;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MeControllerTest extends AbstractTest
{
    use ResetDatabase;
    use Factories;

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
