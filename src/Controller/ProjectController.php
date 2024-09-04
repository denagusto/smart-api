<?php

namespace App\Controller;

use App\Entity\Project;
use App\Request\CreateRequest;
use App\Request\Project\CreateRequest as ProjectCreateRequest;
use App\Service\ProjectService;
use App\Service\ResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectController extends AbstractController
{
    private ProjectService $projectService;
    private ResponseService $responseService;

    public function __construct(ProjectService $projectService, ResponseService $responseService)
    {
        $this->projectService = $projectService;
        $this->responseService = $responseService;
    }

    #[Route('/api/projects', name: 'project_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->responseService->createResponse(
                'error', 
                'User not authenticated', 
                null, 
                Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $projectRequest = new ProjectCreateRequest();
        $projectRequest->setName($data['name'] ?? '');
        $projectRequest->setLocation($data['location'] ?? '');
        $projectRequest->setStage($data['stage'] ?? '');
        $projectRequest->setCategory($data['category'] ?? '');
        $projectRequest->setFee($data['fee'] ?? 0);
        $projectRequest->setStartDate(new \DateTime($data['startDate'] ?? 'now'));
        $projectRequest->setDetails($data['details'] ?? '');
        $projectRequest->setCategoryText($data['categoryText'] ?? ''); // Handle free text category

        // Validate the request
        $errors = $validator->validate($projectRequest);
        if (count($errors) > 0) {
            return $this->responseService->createResponse(
                'error', 
                'Project failed to save', 
                ['errors' => (string) $errors], 
                Response::HTTP_BAD_REQUEST);
        }

        // Map the request to a Project entity
        $project = new Project();
        $project->setName($projectRequest->getName());
        $project->setLocation($projectRequest->getLocation());
        $project->setStage($projectRequest->getStage());

        // Use the free text category if 'Other' is selected
        if ($projectRequest->getCategory() === 'Others') {
            $project->setCategoryText($projectRequest->getCategoryText());
        }

        $project->setCategory($projectRequest->getCategory());

        $project->setFee($projectRequest->getFee());
        $project->setStartDate($projectRequest->getStartDate());
        $project->setDetails($projectRequest->getDetails());
        $project->setCreator($user);

        // Use ORM:
        $this->projectService->createProject($project);

        // Use SP
        // $this->projectService->createProjectWithSP($project);

        // Return the created project as a JSON response
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Project save successfully',
            'data' => [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'location' => $project->getLocation(),
                'stage' => $project->getStage(),
                'category' => $project->getCategory(),
                'categoryText' => $project->getCategoryText(),
                'fee' => $project->getFee(),
                'startDate' => $project->getStartDate()->format('Y-m-d'),
                'details' => $project->getDetails(),
                'creator' => $project->getCreator()->getId(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]], Response::HTTP_OK);
    }

    #[Route('/api/projects/{id}', name: 'project_update', methods: ['PUT'])]
    public function update(string $id, Request $request, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->responseService->createResponse(
                'error', 
                'User not authenticated', 
                null, 
                Response::HTTP_UNAUTHORIZED);
        }

        // Retrieve the project by its ID
        $project = $this->projectService->getProjectById($id);

        if (!$project) {
            return $this->responseService->createResponse(
                'error', 
                'Project not found', 
                null, 
                Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Map the request data to a ProjectRequest object for validation
        $projectRequest = new ProjectCreateRequest();
        $projectRequest->setName($data['name'] ?? $project->getName());
        $projectRequest->setLocation($data['location'] ?? $project->getLocation());
        $projectRequest->setStage($data['stage'] ?? $project->getStage());
        $projectRequest->setCategory($data['category'] ?? $project->getCategory());
        $projectRequest->setFee($data['fee'] ?? $project->getFee());
        $projectRequest->setStartDate(isset($data['startDate']) ? new \DateTime($data['startDate']) : $project->getStartDate());
        $projectRequest->setDetails($data['details'] ?? $project->getDetails());
        $projectRequest->setCategoryText($data['categoryText'] ?? $project->getCategoryText()); // Handle free text category

        // Validate the request
        $errors = $validator->validate($projectRequest);
        if (count($errors) > 0) {
            return $this->responseService->createResponse(
                'error', 
                'Project failed to update', 
                ['errors' => (string) $errors], 
                Response::HTTP_BAD_REQUEST);
        }

        // Update the project entity with the validated data
        $project->setName($projectRequest->getName());
        $project->setLocation($projectRequest->getLocation());
        $project->setStage($projectRequest->getStage());

        // Use the free text category if 'Others' is selected
        if ($projectRequest->getCategory() === 'Others') {
            $project->setCategoryText($projectRequest->getCategoryText());
        } else {
            $project->setCategoryText(null);
        }

        $project->setCategory($projectRequest->getCategory());
        $project->setFee($projectRequest->getFee());
        $project->setStartDate($projectRequest->getStartDate());
        $project->setDetails($projectRequest->getDetails());
        $project->setUpdatedAt(new \DateTime());

        // Persist the changes
        // Use ORM:
        // $this->projectService->updateProject($project);

        // Use SP
        $this->projectService->updateProjectWithSP($project);

        // Return the updated project as a JSON response
        return $this->responseService->createResponse(
            'success', 
            'Project updated successfully', 
            [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'location' => $project->getLocation(),
                'stage' => $project->getStage(),
                'category' => $project->getCategory(),
                'categoryText' => $project->getCategoryText(),
                'fee' => $project->getFee(),
                'startDate' => $project->getStartDate()->format('Y-m-d'),
                'details' => $project->getDetails(),
                'creator' => $project->getCreator()->getId(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
            ], Response::HTTP_OK);
    }

    #[Route('/api/projects/{id}', name: 'project_delete', methods: ['DELETE'])]
    public function delete(string $id): Response
    {
        $project = $this->projectService->getProjectById($id);
        if (!$project) {
            return $this->responseService->createResponse(
                'error', 
                'Project not found', 
                null, 
                Response::HTTP_NOT_FOUND);
        }

        // Use ORM:
        $this->projectService->deleteProject($project);

        // Or use a stored procedure:
        // $this->projectService->deleteProjectWithSP($project);

        return $this->responseService->createResponse(
            'success', 
            'Project deleted successfully', 
            null, Response::HTTP_OK);
    }

    #[Route('/api/projects/{id}', name: 'project_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        // Use ORM:
        $project = $this->projectService->getProjectById($id);
        // dd($project);
        if (!$project) {
            return $this->responseService->createResponse(
                'error', 
                'Project not found', 
                null, 
                Response::HTTP_NOT_FOUND);
        }

        return $this->responseService->createResponse(
            'success', 
            'Project updated successfully', 
            [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'location' => $project->getLocation(),
                'stage' => $project->getStage(),
                'category' => $project->getCategory(),
                'categoryText' => $project->getCategoryText(),
                'fee' => $project->getFee(),
                'startDate' => $project->getStartDate()->format('Y-m-d'),
                'details' => $project->getDetails(),
                'creator' => $project->getCreator()->getId(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
            ], Response::HTTP_OK);
    }

    #[Route('/api/projects', name: 'project_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = [
            'name' => $request->query->get('name'),
            'category' => $request->query->get('category'),
        ];

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $paginator = $this->projectService->getAllProjects($filters, $page, $limit);

        $projects = [];
        foreach ($paginator as $project) {
            $projects[] = [
                'id' => $project->getId(),
                'code' => $project->getCode(),
                'name' => $project->getName(),
                'stage' => $project->getStage(),
                'category' => $project->getCategory(),
                'startDate' => $project->getStartDate()->format('Y-m-d'),
            ];
        }

        return $this->json([
            'total' => count($paginator),
            'page' => $page,
            'limit' => $limit,
            'projects' => $projects,
        ]);
    }
}
