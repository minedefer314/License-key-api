<?php

namespace App\Controller;

use App\DTO\RequestDataDTO;
use App\DTO\RequestDTO;
use App\Entity\Session;
use App\Repository\LicenseRepository;
use App\Service\PayloadDecryptionService;
use App\Validator\LicenseValidity\IsValidLicense;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SessionController extends AbstractController
{
    #[Route('/api/session/create', name: 'api_session_create', methods: ['POST'])]
    public function createSession(
        Request $request,
        PayloadDecryptionService $decryptionService,
        LicenseRepository $licenseRepository,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        // Construct DTO using request data
        $data = json_decode($request->getContent(), true);
        $requestDTO = new RequestDTO();
        $requestDTO->payload = $data['payload'] ?? '';
        $requestDTO->key = $data['key'] ?? '';
        $requestDTO->iv = $data['iv'] ?? '';

        // Validate DTO
        $error = $validator->validate($requestDTO)[0] ?? null;
        if ($error) {
            return new JsonResponse(
                [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => $error->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Attempt to decrypt data using validated DTO
        $data = $decryptionService->decryptPayload(
            payload: $requestDTO->payload,
            cryptedKeyBase64: $requestDTO->key,
            ivBase64: $requestDTO->iv
        );

        if(is_string($data))
            return new JsonResponse(
                [
                    "status" => Response::HTTP_BAD_REQUEST,
                    "message" => $data
                ],
                Response::HTTP_BAD_REQUEST
            );

        // Construct DTO using request payload
        $requestDataDTO = new RequestDataDTO();
        $requestDataDTO->expiresAt = $data['expiresAt'] ?? '';
        $requestDataDTO->licenseKey = $data['licenseKey'] ?? '';

        // Validate DTO
        $error = $validator->validate($requestDataDTO)[0] ?? null;

        if ($error) {
            $responseCode = match ($error->getCode()) {
                IsValidLicense::class => Response::HTTP_NOT_FOUND,
                default => Response::HTTP_BAD_REQUEST,
            };

            return new JsonResponse(
                ['message' => $error->getMessage()],
                $responseCode
            );
        }

        // Search for license using given key
        $license = $licenseRepository->findByLicenseKey($data["license_key"]);

        // Make sure the found license doesn't already have an active session
        if($license->getSessions()->filter(function (Session $session) {
            return $session->isActive();
        })->count() > 0)
            return new JsonResponse(
                ["message" => "This license already has an active session."],
                Response::HTTP_CONFLICT
            );

        // TODO: additional checks to prevent license key sharing
        // TODO: session creation

        return new JsonResponse(
            ["message" => "Session allowed."],
            Response::HTTP_CREATED
        );
    }
}
