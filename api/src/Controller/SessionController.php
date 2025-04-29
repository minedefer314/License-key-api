<?php

namespace App\Controller;

use App\Repository\LicenseRepository;
use App\Service\PayloadDecryptionService;
use App\DTO\CreateSessionRequest;
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
    ): JsonResponse {

        // Construct DTO using request data
        $data = json_decode($request->getContent(), true);
        $dto = new CreateSessionRequest();
        $dto->payload = $data['payload'] ?? '';
        $dto->key = $data['key'] ?? '';
        $dto->iv = $data['iv'] ?? '';

        // Validate DTO
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json([
                'message' => 'Invalid request.',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Attempt to decrypt data using validated DTO
        $data = $decryptionService->decryptPayload($dto->payload, $dto->key, $dto->iv);
        if(!$data)
            return new JsonResponse(["message" => "Invalid RSA encoding or AES key."], Response::HTTP_BAD_REQUEST);

        // Check data validity
        if (
            !isset($data["expiresAt"])      ||     // More attributes to add
            !isset($data["license_key"])
        ) return new JsonResponse(["message" => "Invalid payload."], Response::HTTP_BAD_REQUEST);

        if (!is_numeric($data["expiresAt"]) || $data["expiresAt"] < time())
            return new JsonResponse(["message" => "Expired payload."], Response::HTTP_BAD_REQUEST);

        // TODO: check license key validity
        // TODO: checks to prevent 2 sessions on same license
        // TODO: additional checks to prevent license key sharing

        return new JsonResponse("", Response::HTTP_CREATED);
    }
}
