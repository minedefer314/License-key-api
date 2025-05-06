<?php

namespace App\Controller\Api;

use App\Service\Api\SessionManagement\SessionManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SessionController extends AbstractController
{
    #[Route('/api/session/create', name: 'api_session_create', methods: ['POST'])]
    public function createSession(Request $request, SessionManagementService $sessionManager): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true);

        try {
            $operation = $sessionManager->processCreationRequest($requestBody);
        }
        catch (\Exception $exception) {
            return new JsonResponse(
                [
                    "message" => $exception->getMessage()
                ],
                $exception->getCode()
            );
        }

        return new JsonResponse(
            [
                "message" => "Session " . $operation . "."
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/api/session/terminate', name: 'api_session_terminate', methods: ['POST'])]
    public function terminateSession(Request $request, SessionManagementService $sessionManager): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true);

        try {
            $sessionManager->processTerminationRequest($requestBody);
        }
        catch (\Exception $exception) {
            return new JsonResponse(
                [
                    "message" => $exception->getMessage()
                ],
                $exception->getCode()
            );
        }

        return new JsonResponse(
            [
                "message" => "Session terminated."
            ],
            Response::HTTP_OK
        );
    }
}
