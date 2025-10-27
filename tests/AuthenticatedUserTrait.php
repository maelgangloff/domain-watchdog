<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;

trait AuthenticatedUserTrait
{
    public static function createClientWithCredentials(string $token): Client
    {
        return static::createClient([], [
            'headers' => [
                'authorization' => 'Bearer '.$token,
            ],
        ]);
    }

    public static function getToken(User $testUser): string
    {
        $response = static::createClient()->request('POST', '/api/login', [
            'json' => [
                'email' => $testUser->getEmail(),
                'password' => $testUser->getPlainPassword(),
            ],
        ]);

        $data = $response->toArray();

        return $data['token'];
    }
}
