<?php

namespace App\Tests\Api\Session;

use App\Entity\License;
use App\Entity\Location;
use App\Entity\Session;
use Symfony\Component\Console\Application;
use App\Repository\LicenseRepository;
use App\Repository\LocationRepository;
use App\Repository\SessionRepository;
use App\Service\Encryption\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ExpirationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private EncryptionService $encryptionService;
    private SessionRepository $sessionRepository;
    private LicenseRepository $licenseRepository;
    private LocationRepository $locationRepository;

    private function purgeDatabase(): void
    {
        if ($_ENV['APP_ENV'] !== 'test') return;

        $sessions = $this->sessionRepository->findAll();
        foreach ($sessions as $session) {
            $this->entityManager->remove($session);
        }

        $licenses = $this->licenseRepository->findAll();
        foreach ($licenses as $license) {
            $this->entityManager->remove($license);
        }

        $locations = $this->locationRepository->findAll();
        foreach ($locations as $location) {
            $this->entityManager->remove($location);
        }

        $this->entityManager->flush();
    }

    public function setUp(): void
    {
        parent::setUp();
        $client = static::createClient();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->sessionRepository = static::getContainer()->get(SessionRepository::class);
        $this->licenseRepository = static::getContainer()->get(LicenseRepository::class);
        $this->locationRepository = static::getContainer()->get(LocationRepository::class);
        $this->purgeDatabase();

        $privateKey = $client->getContainer()->getParameter('PRIVATE_KEY');
        $this->encryptionService = new EncryptionService($privateKey);
    }

    private function createLicense(string $owner): License
    {
        $license = new License();
        $license->setOwner($owner);

        $this->entityManager->persist($license);
        $this->entityManager->flush();

        return $license;
    }

    private function createLocation(string $ip): Location
    {
        $location = new Location($ip);

        $this->entityManager->persist($location);
        $this->entityManager->flush();

        return $location;
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

    private function executeTerminateExpiredSessionsCommand(int $expirationDelay): void
    {
        $client = static::getClient();
        $container = static::getContainer();

        $application = new Application();

        $command = $container->get(\App\Command\TerminateExpiredSessionsCommand::class);
        $application->add($command);

        $testedCommand = $application->find('app:terminate-expired-sessions');
        $commandTester = new CommandTester($testedCommand);

        $commandTester->execute([
            'command' => $testedCommand->getName(),
            'expiration' => 5
        ]);

        $commandTester->getDisplay();
    }

    private function createValidRequestBody(string $licenseKey, string $ipAddress): array
    {
        $binaryAes = random_bytes(32);
        $binaryIv = random_bytes(16);
        $dataArray = [
            "expiresAt" => time() + 10,
            "licenseKey" => $licenseKey,
            "ipAddress" => $ipAddress
        ];
        $data = json_encode($dataArray);

        $encryptedData = $this->encryptionService
            ->aesEncrypt(
                rawData: $data,
                binaryKey: $binaryAes,
                binaryIv: $binaryIv
            );
        $encryptedAes = $this->encryptionService->rsaEncrypt($binaryAes);

        return [
            "payload" => base64_encode($encryptedData),
            "key" => base64_encode($encryptedAes),
            "iv" => base64_encode($binaryIv),
        ];
    }

    public function testSessionNotExpired(): void
    {
        // Create a valid session
        $license = $this->createLicense("notExpiredOwner");
        $location = $this->createLocation("127.0.0.1");

        $requestBody = $this->createValidRequestBody($license->getUuid(), $location->getIpAddr());

        $this->assertValidResponse(
            payload: $requestBody["payload"],
            key: $requestBody["key"],
            iv: $requestBody["iv"],
            expectedCode: 201,
            expectedMessage: "Session created."
        );

        $session = $license->getActiveSession();
        $this->assertNotNull($session);
        $this->assertTrue($session->isActive());

        $this->executeTerminateExpiredSessionsCommand(5);

        $this->assertTrue($session->isActive());
    }

    public function testSessionExpired(): void
    {
        $license = $this->createLicense("expiredSessionOwner");
        $location = $this->createLocation("127.0.0.1");

        $requestBody = $this->createValidRequestBody($license->getUuid(), $location->getIpAddr());

        $this->assertValidResponse(
            payload: $requestBody["payload"],
            key: $requestBody["key"],
            iv: $requestBody["iv"],
            expectedCode: 201,
            expectedMessage: "Session created."
        );

        $session = $license->getActiveSession();
        $this->assertNotNull($session);
        $this->assertTrue($session->isActive());

        $sessionId = $session->getId();

        $expiredTime = (new \DateTimeImmutable())->modify("-6 minutes");

        // Manually expire the created session
        $this->entityManager->createQueryBuilder()
            ->update(Session::class, "s")
            ->set("s.lastUpdated", ":expiredTime")
            ->where("s.id = :id")
            ->setParameter("expiredTime", $expiredTime)
            ->setParameter("id", $sessionId)
            ->getQuery()
            ->execute();

        $this->executeTerminateExpiredSessionsCommand(5);

        $this->assertNotTrue($session->isActive());
    }
}