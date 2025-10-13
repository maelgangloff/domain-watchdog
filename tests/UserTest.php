<?php

namespace App\Tests;

use App\Entity\User;
use App\Factory\UserFactory;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class UserTest extends AbstractTest
{
    use ResetDatabase;
    use Factories;

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetMyProfile(): void
    {
        $testUser = UserFactory::createOne();
        $client = UserTest::createClientWithCredentials(UserTest::getToken($testUser));

        $client->loginUser($testUser);
        $client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }
}
