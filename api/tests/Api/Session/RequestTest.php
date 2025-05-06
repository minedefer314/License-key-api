<?php

namespace App\Tests\Api\Session;

use App\Service\Encryption\EncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RequestTest extends WebTestCase
{
    private EncryptionService $encryptionService;


    public function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        $privateKey = $client->getContainer()->getParameter('PRIVATE_KEY');
        $this->encryptionService = new EncryptionService($privateKey);
    }

    private function assertValidResponse(
        string $payload,
        string $key,
        string $iv,
        string $expectedMessage
    ): void
    {
        $client = static::getClient();

        $client->request(
            method: 'POST',
            uri: '/api/session/create',
            content: json_encode(
                [
                    "payload" => $payload,
                    "key" => $key,
                    "iv" => $iv,
                ]
            )
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertJson($content);

        $jsonContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $jsonContent);
        $this->assertEquals($expectedMessage, $jsonContent['message']);
    }

    public function testInvalidRequest(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $this->assertValidResponse(
            payload: "",
            key: "",
            iv: "",
            expectedMessage: "A data payload is required."
        );
    }

    public function testInvalidRsaEncryption(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $this->assertValidResponse(
            payload: "lol",
            key: "lol",
            iv: "lol",
            expectedMessage: "Invalid RSA encryption."
        );
    }

    public function testIvNotBase64(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $encryptedAesKey = $this->encryptionService->rsaEncrypt(random_bytes(32));

        $this->assertValidResponse(
            payload: "lol",
            key: base64_encode($encryptedAesKey),
            iv: "$",
            expectedMessage: "IV is not base64 encoded."
        );
    }

    public function testIvNotBinary(): void
    {
        if ($_ENV['APP_ENV'] !== 'test')
            $this->markTestSkipped('This test may only be executed in the test env.');

        $encryptedAesKey = $this->encryptionService->rsaEncrypt(random_bytes(32));

        $this->assertValidResponse(
            payload: "lol",
            key: base64_encode($encryptedAesKey),
            iv: base64_encode("hello"),
            expectedMessage: "IV is not binary."
        );
    }

    public function testIvIncorrectLength(): void
    {
        if ($_ENV['APP_ENV'] !== 'test')
            $this->markTestSkipped('This test may only be executed in the test env.');

        $encryptedAesKey = $this->encryptionService->rsaEncrypt(random_bytes(32));

        $this->assertValidResponse(
            payload: "lol",
            key: base64_encode($encryptedAesKey),
            iv: base64_encode(random_bytes(32)),
            expectedMessage: "Invalid IV length."
        );
    }

    public function testInvalidAesEncryption(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $encryptedAes = $this->encryptionService->rsaEncrypt(random_bytes(32));

        $this->assertValidResponse(
            payload: "lol",
            key: base64_encode($encryptedAes),
            iv: base64_encode(random_bytes(16)),
            expectedMessage: "Invalid AES encryption."
        );
    }
}