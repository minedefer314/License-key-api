<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateSessionTest extends WebTestCase
{
    public function testInvalidRequestCase(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/session/create');

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertJson($content);

        $jsonContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $jsonContent);
        $this->assertEquals('A data payload is required.', $jsonContent['message']);
    }

    public function testInvalidRsaEncryption(): void
    {
        $client = static::createClient();
        $client->request(
            method: 'POST',
            uri: '/api/session/create',
            content: json_encode(
                [
                    "payload" => "lol",
                    "key" => "lol",
                    "iv" => "lol",
                ]
            )
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertJson($content);

        $jsonContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $jsonContent);
        $this->assertEquals('Invalid RSA encryption.', $jsonContent['message']);
    }
}
