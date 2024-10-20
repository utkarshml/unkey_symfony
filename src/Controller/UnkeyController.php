<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\UnkeyService;

class UnkeyController extends AbstractController
{
    private UnkeyService $unkeyService;
    public function __construct(UnkeyService $unkeyService)
    {
        $this->unkeyService = $unkeyService; // Initialize the property here
    }

    
    #[Route('/create', name: 'unkey_create' ,methods:["POST"])]
        public function createUnkey(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $apiId = $data['apiId'];
        $name = $data['name'];
        $data = [
            "apiId" => $apiId,
            "name" => $name
        ];
        try {
            $response = $this->unkeyService->createKey(
                $data,  
            );
            return new JsonResponse($response);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
 
    #[Route('/key', name: 'information_of_key' ,methods:["GET"])]
    public function getKey(Request $request): JsonResponse
    {
        $content = $request->getContent();
        $data = json_decode($content, true);
        $keyId = $data['keyId'] ?? null;
        if (!$keyId) {
            return new JsonResponse(['error' => 'No authorization header found'], 400);
        }
        $data = [
            'keyId' => $keyId
        ];
        try {
            $response = $this->unkeyService->getKey($data, );
            return new JsonResponse($response, 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

// This is protected routes by Unkey 

    #[Route('/protected', name: 'protected_by_unkey' ,methods:["GET"])]
    public function myPaidApi(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('x-api-key');
        if (!$authHeader) {
            return new JsonResponse(['error' => 'No authorization header found'], 400);
        }
        $data = [
            'key' => $authHeader
        ];
        try {
            $response = $this->unkeyService->verifyKey($data, );
            if(!$response['result']['valid']){
                return new JsonResponse(['message' => 'You can not access'], 401);
            }
            return new JsonResponse(['message' => ' You can access'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
