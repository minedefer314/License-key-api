<?php

namespace App\Controller;

use App\Service\PayloadDecryptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SessionController extends AbstractController
{
    #[Route('/api/session/create', name: 'api_session_create', methods: ['POST'])]
    public function createSession(Request $request, PayloadDecryptionService $decryptionService): JsonResponse
    {
        $payload = $request->get('payload');
        $cryptedKey = $request->get("key");
        $iv = $request->get("iv");
        $data = $decryptionService->decryptPayload($payload, $cryptedKey, $iv);

        if (!isset($data["expiresAt"])) return new JsonResponse("", Response::HTTP_BAD_REQUEST);

        // Continue the logics

        return new JsonResponse("", Response::HTTP_CREATED);
    }
}
