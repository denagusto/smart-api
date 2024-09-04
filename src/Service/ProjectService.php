<?php

namespace App\Service;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ProjectService
{
    private $projectRepository;
    private $entityManager;

    public function __construct(ProjectRepository $projectRepository, EntityManagerInterface $entityManager)
    {
        $this->projectRepository = $projectRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Create a new project using ORM.
     */
    public function createProject(Project $project): void
    {
        $projectCode = $this->generateUniqueProjectCode($project->getName());
        $project->setCode($projectCode);

        $this->projectRepository->save($project);
    }

    private function generateUniqueProjectCode(string $name): string
    {
        do {
            $projectCode = $this->generateProjectCode($name);
        } while ($this->isProjectCodeExists($projectCode));

        return $projectCode;
    }

    private function generateProjectCode(string $name): string
    {
        $initials = strtoupper(substr($name, 0, 2)); // First 2 letters of the name
        $randomNumber = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); // 4-digit random number
        
        return $initials . $randomNumber;
    }

    private function isProjectCodeExists(string $projectCode): bool
    {
        $existingProject = $this->entityManager->getRepository(Project::class)->findOneBy(['code' => $projectCode]);

        return $existingProject !== null;
    }

    /**
     * Update an existing project using ORM.
     */
    public function updateProject(Project $project): void
    {
        $this->projectRepository->update($project);
    }

    /**
     * Delete a project using ORM.
     */
    public function deleteProject(Project $project): void
    {
        $this->projectRepository->remove($project);
    }

    /**
     * Find a project by its ID using ORM.
     */
    public function getProjectById($projectId): ?Project
    {
        return $this->projectRepository->find($projectId);
    }

    /**
     * Create a new project using a stored procedure.
     */
    public function createProjectWithSP(Project $project): void
    {
        $projectCode = $this->generateUniqueProjectCode($project->getName());
        $project->setCode($projectCode);
        
        $this->projectRepository->createProjectWithSP($project);
    }

    /**
     * Update an existing project using a stored procedure.
     */
    public function updateProjectWithSP(Project $project): void
    {
        $this->projectRepository->updateProjectWithSP($project);
    }

    /**
     * Delete a project using a stored procedure.
     */
    public function deleteProjectWithSP(Project $project): void
    {
        $this->projectRepository->deleteProjectWithSP($project);
    }

    /**
     * Find a project by its ID using a stored procedure.
     */
    public function getProjectByIdWithSP($projectId): ?Project
    {
        return $this->projectRepository->findByIdWithSP($projectId);
    }

    /**
     * Get all projects with optional filtering and pagination.
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return Paginator
     */
    public function getAllProjects(array $filters = [], int $page = 1, int $limit = 10): Paginator
    {
        $queryBuilder = $this->projectRepository->createQueryBuilder('p');

        // Apply filters
        if (isset($filters['name'])) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE LOWER(:name)')
                ->setParameter('name', '%' . strtolower($filters['name']) . '%');
        }
        
        if (isset($filters['category'])) {
            $queryBuilder->andWhere('LOWER(p.category) = LOWER(:category)')
                ->setParameter('category', strtolower($filters['category']));
        }

        // Pagination
        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Get paginated results
        $paginator = new Paginator($queryBuilder);

        return $paginator;
    }
}
