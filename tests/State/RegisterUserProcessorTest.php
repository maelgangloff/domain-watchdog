<?php

namespace App\Tests\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;

final class RegisterUserProcessorTest extends ApiTestCase
{
    use Factories;

    protected static ContainerInterface $container;
    protected static EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        RegisterUserProcessorTest::$container = RegisterUserProcessorTest::getContainer();
        RegisterUserProcessorTest::$entityManager = RegisterUserProcessorTest::$container->get(EntityManagerInterface::class);
    }

    public function testRegister(): void
    {
        $testUser = UserFactory::createOne();
        RegisterUserProcessorTest::$entityManager->remove($testUser);
        RegisterUserProcessorTest::$entityManager->flush();

        $client = $this->createClient();
        $client->request('POST', '/api/register', [
            'json' => [
                'email' => $testUser->getEmail(),
                'password' => $testUser->getPlainPassword(),
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
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
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
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
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
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
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
