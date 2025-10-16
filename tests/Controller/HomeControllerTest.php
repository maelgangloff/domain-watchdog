<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testConnectSsoReturnNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login/oauth');

        $this->assertResponseStatusCodeSame(404);
    }
}
