<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zenstruck\Foundry\Test\Factories;

final class RegistrationControllerTest extends ApiTestCase
{
    use Factories;

    protected static ContainerInterface $container;
    protected static EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        RegistrationControllerTest::$container = RegistrationControllerTest::getContainer();
        RegistrationControllerTest::$entityManager = RegistrationControllerTest::$container->get(EntityManagerInterface::class);
    }

    public function testRegister(): void
    {
        $testUser = UserFactory::createOne();
        RegistrationControllerTest::$entityManager->remove($testUser);
        RegistrationControllerTest::$entityManager->flush();

        $client = $this->createClient();
        $client->request('POST', '/api/register', [
            'json' => [
                'email' => $testUser->getEmail(),
                'password' => $testUser->getPlainPassword(),
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testRegisterEmptyEmail(): void
    {
        $client = $this->createClient();
        $client->request('POST', '/api/register', [
            'json' => [
                'email' => '',
                'password' => 'MySuperPassword123',
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterEmptyPassword(): void
    {
        $client = $this->createClient();
        $client->request('POST', '/api/register', [
            'json' => [
                'email' => 'test@domainwatchdog.eu',
                'password' => '',
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterWeakPassword(): void
    {
        $client = $this->createClient();
        $client->request('POST', '/api/register', [
            'json' => [
                'email' => 'test@domainwatchdog.eu',
                'password' => '123',
            ],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }
}
