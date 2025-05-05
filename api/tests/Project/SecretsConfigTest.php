<?php

namespace App\Tests\Project;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecretsConfigTest extends KernelTestCase
{
    public function testDatabaseURL(): void
    {
        $container = static::getContainer();

        $this->assertNotNull($container->getParameter('DATABASE_URL'), 'DATABASE_URL secret is not set.');
    }

    public function testPrivateKey(): void
    {
        $container = static::getContainer();

        $this->assertNotNull($container->getParameter('PRIVATE_KEY'), 'RSA_PRIVATE_KEY_BASE64 secret is not set.');
    }
}
