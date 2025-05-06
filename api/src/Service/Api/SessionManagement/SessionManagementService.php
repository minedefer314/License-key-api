<?php

namespace App\Service\Api\SessionManagement;

use App\DTO\RequestDataDTO;
use App\DTO\RequestDTO;
use App\Repository\LicenseRepository;
use App\Service\Encryption\PayloadDecryptionService;
use App\Validator\LicenseValidity\IsValidLicense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SessionManagementService
{
    private PayloadDecryptionService $decryptionService;
    private SessionFactory $sessionFactory;
    private LicenseRepository $licenseRepository;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        PayloadDecryptionService $decryptionService,
        SessionFactory $sessionFactory,
        LicenseRepository $licenseRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    )
    {
        $this->decryptionService = $decryptionService;
        $this->sessionFactory = $sessionFactory;
        $this->licenseRepository = $licenseRepository;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }


    // TODO: Add verifications to prevent license key sharing
    public function processCreationRequest(array $requestBody): string
    {
        // Validate the json request (attributes, encryption)
        $requestDTO = $this->validateRequest($requestBody);

        // Decrypt and extract data from the request payload attribute
        $data = $this->getDataFromRequest($requestDTO);

        // Validate the extracted data (attributes, expiration, license validity)
        $requestDataDTO = $this->validateRequestData($data);

        // Attempt to create a new session or update an existant session and return the operation processed (created or updated)
        return $this->createUpdateSession($requestDataDTO);
    }

    public function processTerminationRequest(array $requestBody): void
    {
        // Validate the json request (attributes, encryption)
        $requestDTO = $this->validateRequest($requestBody);

        // Decrypt and extract data from the request payload attribute
        $data = $this->getDataFromRequest($requestDTO);

        // Validate the extracted data (attributes, expiration, license validity)
        $requestDataDTO = $this->validateRequestData($data);

        $this->terminateSession($requestDataDTO);
    }

    private function validateRequest(array $requestBody): RequestDTO
    {
        $requestDTO = new RequestDTO();
        $requestDTO->payload = $requestBody['payload'] ?? '';
        $requestDTO->key = $requestBody['key'] ?? '';
        $requestDTO->iv = $requestBody['iv'] ?? '';

        $error = $this->validator->validate($requestDTO)[0] ?? null;
        if ($error) {
            throw new \Exception($error->getMessage(), 400);
        }

        return $requestDTO;
    }

    private function getDataFromRequest(RequestDTO $requestDTO): array
    {
        $data = $this->decryptionService->decryptPayload(
            payload: $requestDTO->payload,
            cryptedKeyBase64: $requestDTO->key,
            ivBase64: $requestDTO->iv
        );

        if(is_string($data)) {
            throw new \Exception($data, 400);
        }

        return $data;
    }

    private function validateRequestData(array $data): RequestDataDTO
    {
        $requestDataDTO = new RequestDataDTO();
        $requestDataDTO->expiresAt = $data['expiresAt'] ?? '';
        $requestDataDTO->licenseKey = $data['licenseKey'] ?? '';
        $requestDataDTO->ipAddress = $data['ipAddress'] ?? '';

        $error = $this->validator->validate($requestDataDTO)[0] ?? null;

        if ($error) {
            $thrownExceptionCode = match ($error->getCode()) {
                IsValidLicense::class => 404,
                default => 400,
            };

            throw new \Exception($error->getMessage(), $thrownExceptionCode);
        }

        return $requestDataDTO;
    }

    private function createUpdateSession($requestDataDTO): string
    {
        $license = $this->licenseRepository->findByLicenseKey($requestDataDTO->licenseKey);
        $activeSession = $license->getActiveSession();

        if ($activeSession && $activeSession->getLocation()->getIpAddr() !== $requestDataDTO->ipAddress) {
            throw new \Exception("This license already has an active session.", 409);
        }
        else if ($activeSession && $activeSession->getLocation()->getIpAddr() === $requestDataDTO->ipAddress) {
            $activeSession->update();
            $this->entityManager->flush();
            return "updated";
        }

        $this->sessionFactory->createSessionFromDTO($requestDataDTO);
        return "created";
    }

    private function terminateSession(RequestDataDTO $requestDataDTO): void
    {
        $license = $this->licenseRepository->findByLicenseKey($requestDataDTO->licenseKey);
        $activeSession = $license->getActiveSession();

        if(!$activeSession) {
            throw new \Exception("No session found.", 404);
        }

        $activeSession->terminate();
        $this->entityManager->flush();
    }
}