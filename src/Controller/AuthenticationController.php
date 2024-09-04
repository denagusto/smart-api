<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthenticationController extends AbstractController
{
    private UserService $userService;
    private JWTTokenManagerInterface $JWTManager;

    public function __construct(UserService $userService, JWTTokenManagerInterface $JWTManager)
    {
        $this->userService = $userService;
        $this->JWTManager = $JWTManager;
    }

    #[Route('/api/login', name: 'json_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->findUserByEmail($email);

        if (!$user || !$this->userService->validatePassword($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $token = $this->JWTManager->create($user);

        return new JsonResponse([
            'message' => 'Success',
            'user'  => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
