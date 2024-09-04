<?php

namespace App\Tests\Controller;

use App\Controller\ProjectController;
use App\Entity\Project;
use App\Entity\User;
use App\Request\Project\CreateRequest;
use App\Service\ProjectService;
use App\Service\ResponseService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProjectControllerTest extends TestCase
{
    use ProphecyTrait;

    private $projectService;
    private $responseService;
    private $validator;
    private $controller;

    protected function setUp(): void
    {
        // Set up the mocked services using Prophecy
        $this->projectService = $this->prophesize(ProjectService::class);
        $this->responseService = $this->prophesize(ResponseService::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new ProjectController(
            $this->projectService->reveal(),
            $this->responseService->reveal()
        );
    }

    /**
     * Test case: create project without user
     */
    public function testCreateProjectWithoutUser(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode([]));

        // Initialize the controller with no user (unauthenticated)
        $this->controller->setContainer($this->getContainerWithUser(null));

        // Mock response service to return unauthorized response
        $this->responseService->createResponse(
            'error',
            'User not authenticated',
            null,
            JsonResponse::HTTP_UNAUTHORIZED
        )->willReturn(new JsonResponse(['status' => 'error', 'message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED));

        // Call the create method on the controller
        $response = $this->controller->create($request->reveal(), $this->validator->reveal());

        // Assert the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * Test case: create project with validation errors
     */
    public function testCreateProjectWithValidationErrors(): void
    {
        // Create a mock request with invalid data (e.g., missing name)
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode(['name' => '']));

        // Create a list of constraint violations (validation errors)
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'This field is required', // message
                null,                     // message template
                [],                        // parameters
                '',                        // root
                'name',                    // property path
                null                       // invalid value
            ),
        ]);

        // Mock the validator to return validation errors
        $this->validator->validate(Argument::any())->willReturn($violations);

        // Mock the response service to return a 400 Bad Request response
        $this->responseService->createResponse(
            'error',
            'Project failed to save',
            Argument::type('array'),
            JsonResponse::HTTP_BAD_REQUEST
        )->willReturn(new JsonResponse([
            'status' => 'error',
            'message' => 'Project failed to save',
            'data' => ['errors' => 'Some validation errors']
        ], JsonResponse::HTTP_BAD_REQUEST));

        // Simulate an authenticated user
        $user = $this->prophesize(UserInterface::class);
        $this->controller->setContainer($this->getContainerWithUser($user->reveal()));

        // Call the create method on the controller
        $response = $this->controller->create($request->reveal(), $this->validator->reveal());

        // Assert that the response status code is 400 Bad Request
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
    }


    /**
     * Test case: Successful project creation
     */
    public function testCreateProjectSuccessfully(): void
    {
        // Step 1: Mock the request with valid project data
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode([
            'name' => 'New Community Center',
            'location' => '456 Elm St, Springfield',
            'stage' => 'Design & Documentation',
            'category' => 'Education',
            'categoryText' => 'Community',
            'fee' => '500000',
            'startDate' => '2024-12-01',
            'details' => 'This is a new community center project in Springfield.'
        ]));

        // Step 2: Mock the authenticated user (App\Entity\User)
        $user = $this->prophesize(User::class);  
        $user->getId()->willReturn('12345');  // Mock the user's ID
        
        // Simulate an authenticated user
        $this->controller->setContainer($this->getContainerWithUser($user->reveal()));

        // Step 3: Mock the project entity and ensure the creator is set
        $project = new Project();
        $project->setName('New Community Center');
        $project->setLocation('456 Elm St, Springfield');
        $project->setStage('Design & Documentation');
        $project->setCategory('Education');
        $project->setCategoryText('Community');
        $project->setFee(500000);  
        $project->setStartDate(new \DateTime('2024-12-01'));
        $project->setDetails('This is a new community center project in Springfield.');
        $project->setCreator($user->reveal());  // Set the creator (User entity)

        // Step 4: Mock the validator to return no validation errors
        $this->validator->validate(Argument::any())->willReturn(new ConstraintViolationList());

        // Step 5: Simulate project creation via stored procedure
        $this->projectService->createProject(Argument::any())->shouldBeCalled();

        // Step 6: Mock the response service to return a success JsonResponse
        $this->responseService->createResponse(
            'success',
            'Project saved successfully',
            Argument::type('array'),
            JsonResponse::HTTP_OK
        )->willReturn(new JsonResponse([
            'status' => 'success',
            'message' => 'Project saved successfully',
            'data' => [
                'id' => '12345',
                'name' => 'New Community Center',
                'location' => '456 Elm St, Springfield',
                'stage' => 'Design & Documentation',
                'category' => 'Education',
                'categoryText' => 'Community',
                'fee' => 500000,
                'startDate' => '2024-12-01',
                'details' => 'This is a new community center project in Springfield.',
                'creator' => '12345',  // Ensure the creator ID is returned
                'createdAt' => '2023-09-01 12:00:00',
                'updatedAt' => '2023-09-01 12:00:00',
            ]
        ], JsonResponse::HTTP_OK));

        // Step 7: Call the create method on the controller
        $response = $this->controller->create($request->reveal(), $this->validator->reveal());
        // Step 8: Assert the response is as expected
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
    }




    /**
     * Test case: update project not found
     */
    public function testUpdateProjectNotFound(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode([]));

        // Create a mock of the User entity
        $user = $this->prophesize(User::class);  // Use App\Entity\User
        $user->getId()->willReturn('12345');
        
        // Simulate an authenticated user
        $this->controller->setContainer($this->getContainerWithUser($user->reveal()));

        // Simulate project not found
        $this->projectService->getProjectById('1')->willReturn(null);

        // Mock the response service
        $this->responseService->createResponse(
            'error',
            'Project not found',
            null,
            JsonResponse::HTTP_NOT_FOUND
        )->willReturn(new JsonResponse(['status' => 'error', 'message' => 'Project not found'], JsonResponse::HTTP_NOT_FOUND));

        // Call the update method on the controller
        $response = $this->controller->update('1', $request->reveal(), $this->validator->reveal());

        // Assert the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Test case: successful project update
     */
    public function testUpdateProjectSuccessfully(): void
    {
        // Step 1: Mock the request with updated project data
        $request = $this->prophesize(Request::class);
        $request->getContent()->willReturn(json_encode([
            'name' => 'Updated Project',
            'location' => 'Updated Location',
            'stage' => 'Design',
            'category' => 'Health',
            'fee' => 5000,
            'startDate' => '2023-09-10',
            'details' => 'Updated Details',
        ]));

        // Step 2: Mock the authenticated user (App\Entity\User)
        $user = $this->prophesize(User::class);  
        $user->getId()->willReturn('12345');
        
        // Simulate an authenticated user
        $this->controller->setContainer($this->getContainerWithUser($user->reveal()));

        // Step 3: Initialize the mock project entity
        $project = new Project();
        $project->setName('Original Project');
        $project->setLocation('Original Location');
        $project->setStage('Pre-Construction');
        $project->setCategory('Office');
        $project->setFee(3000);
        $project->setStartDate(new \DateTime('2022-09-01'));
        $project->setDetails('Original Details');
        $project->setCategoryText(null); // Assume no free text category for now

        // *** Ensure the creator is set on the project ***
        $project->setCreator($user->reveal());  // Set the creator (User entity)

        // Simulate project being found
        $this->projectService->getProjectById('1')->willReturn($project);

        // Step 4: Simulate the validator returning no errors
        $this->validator->validate(Argument::any())->willReturn(new ConstraintViolationList());

        // Step 5: Simulate the project being updated via stored procedure
        $this->projectService->updateProjectWithSP(Argument::any())->shouldBeCalled();

        // Step 6: Mock the response service to return a successful update response
        $this->responseService->createResponse(
            'success',
            'Project updated successfully',
            Argument::type('array'),
            JsonResponse::HTTP_OK
        )->willReturn(new JsonResponse([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'data' => null,
        ], JsonResponse::HTTP_OK));

        // Step 7: Call the update method on the controller
        $response = $this->controller->update('1', $request->reveal(), $this->validator->reveal());

        // Step 8: Assert the response is as expected
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(
            ['status' => 'success', 'message' => 'Project updated successfully', 'data' => null],
            json_decode($response->getContent(), true)
        );
    }


    // Utility method to simulate an authenticated user and token storage
    private function getContainerWithUser($user)
    {
        // Mock the Symfony ContainerInterface
        $container = $this->prophesize(ContainerInterface::class);
        
        // Mock the TokenInterface to simulate an authenticated user
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user);  // Ensure getUser() returns the mocked user

        // Mock the TokenStorageInterface to return the mocked token
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        // Ensure the container returns the token storage when requested
        $container->has('security.token_storage')->willReturn(true);  // Ensure has() returns true for token storage
        $container->get('security.token_storage')->willReturn($tokenStorage->reveal());  // Return the mocked token storage

        return $container->reveal();  // Reveal the container prophecy to use in tests
    }
}