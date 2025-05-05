<?php

namespace App\Tests\Api\CreateSession;

use App\Entity\License;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RequestDataTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private EncryptionService $encryptionService;


    public function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $privateKey = $client->getContainer()->getParameter('PRIVATE_KEY');
        $this->encryptionService = new EncryptionService($privateKey);
    }

    private function createLicense(): License
    {
        $license = new License();
        $license->setOwner("an owner");

        $this->entityManager->persist($license);
        $this->entityManager->flush();

        return $license;
    }

    private function assertValidResponse(
        string $payload,
        string $key,
        string $iv,
        int $expectedCode,
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

        $this->assertResponseStatusCodeSame($expectedCode);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertJson($content);

        $jsonContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $jsonContent);
        $this->assertEquals($expectedMessage, $jsonContent['message']);
    }

    public function testInvalidDataJson(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $data = "hi";

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "Data is not valid json."
        );
    }

    public function testMissingExpiration(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );

        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "The expiration timestamp is missing."
        );
    }

    public function testMissingLicense(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => "9999999999"
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "The license key is missing."
        );
    }

    public function testExpiredPayload(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => "-1",
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "The payload is expired."
        );
    }

    public function testInvalidLicense(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => time() + 60,
            "licenseKey" => "hi"
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "Invalid license."
        );
    }

    public function testMissingIp(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $license = $this->createLicense();

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => time() + 10,
            "licenseKey" => $license->getUuid()
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "The ip address is missing."
        );
    }

    public function testInvalidIp(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $license = $this->createLicense();

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => time() + 10,
            "licenseKey" => $license->getUuid(),
            "ipAddress" => "1"
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 400,
            expectedMessage: "The provided ip address is invalid."
        );
    }

    public function testValidLicense(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') {
            $this->markTestSkipped('This test may only be executed in the test env.');
        }

        $license = $this->createLicense();

        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => time() + 10,
            "licenseKey" => $license->getUuid(),
            "ipAddress" => "127.0.0.1"
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        $this->assertValidResponse(
            payload: base64_encode($encryptedData),
            key: base64_encode($encryptedAes),
            iv: base64_encode($binaryIv),
            expectedCode: 201,
            expectedMessage: "Session allowed."
        );
    }
}