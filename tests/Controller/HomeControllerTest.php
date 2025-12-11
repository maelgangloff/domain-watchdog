<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HomeControllerTest extends WebTestCase
{
    public function testConnectSsoReturnNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login/oauth');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
