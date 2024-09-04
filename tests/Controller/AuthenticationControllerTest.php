<?php

namespace App\Tests\Controller;

use App\Controller\AuthenticationController;
use App\Entity\User;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationControllerTest extends TestCase
{
    use ProphecyTrait;

    private $userService;
    private $JWTManager;
    private $controller;

    protected function setUp(): void
    {
        // Set up the mocked services using Prophecy
        $this->userService = $this->prophesize(UserService::class);
        $this->JWTManager = $this->prophesize(JWTTokenManagerInterface::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new AuthenticationController(
            $this->userService->reveal(),
            $this->JWTManager->reveal()
        );
    }

    /**
     * Test case: login with missing credentials
     */
    public function testLoginWithMissingCredentials(): void
    {
        // Simulate a POST request with no email or password
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode([]));

        // Call the login method
        $response = $this->controller->login($request->reveal());

        // Assert that the response is a 400 Bad Request due to missing credentials
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            ['error' => 'Email and password are required'],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * Test case: login with invalid credentials
     */
    public function testLoginWithInvalidCredentials(): void
    {
        // Simulate a POST request with email and password
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode(['email' => 'test@example.com', 'password' => 'invalid_password']));

        // Simulate the UserService returning null (user not found)
        $this->userService->findUserByEmail('test@example.com')->willReturn(null);

        // Call the login method
        $response = $this->controller->login($request->reveal());

        // Assert that the response is a 401 Unauthorized due to invalid credentials
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertEquals(
            ['error' => 'Invalid credentials'],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * Test case: login with valid credentials
     */
    public function testLoginWithValidCredentials(): void
    {
        // Simulate a POST request with valid email and password
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode(['email' => 'test@example.com', 'password' => 'valid_password']));

        // Mock the User object
        $user = $this->prophesize(User::class);
        $user->getUserIdentifier()->willReturn('test@example.com');

        // Simulate the UserService finding the user and validating the password
        $this->userService->findUserByEmail('test@example.com')->willReturn($user->reveal());
        $this->userService->validatePassword($user->reveal(), 'valid_password')->willReturn(true);

        // Simulate the JWT Manager creating a token
        $this->JWTManager->create($user->reveal())->willReturn('mock_jwt_token');

        // Call the login method
        $response = $this->controller->login($request->reveal());

        // Assert that the response is a 200 OK with the token and user information
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(
            [
                'message' => 'Success',
                'user' => 'test@example.com',
                'token' => 'mock_jwt_token'
            ],
            json_decode($response->getContent(), true)
        );
    }
    
}
