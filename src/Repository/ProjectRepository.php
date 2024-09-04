<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    private $connection;

    public function __construct(ManagerRegistry $registry, Connection $connection)
    {
        parent::__construct($registry, Project::class);
        $this->connection = $connection;
    }

    /**
     * Save a project using ORM.
     */
    public function save(Project $project): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($project);
        $entityManager->flush();
    }

    /**
     * Update a project using ORM.
     */
    public function update(Project $project): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->flush();
    }

    /**
     * Remove a project using ORM.
     */
    public function remove(Project $project): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($project);
        $entityManager->flush();
    }

    /**
     * Execute a stored procedure to create a project.
     */
    public function createProjectWithSP(Project $project): void
    {
        $sql = 'CALL create_project(:code, :name, :location, :stage, :category, :fee, :startDate, :details, :creatorId)';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('code', $project->getCode());
        $stmt->bindValue('name', $project->getName());
        $stmt->bindValue('location', $project->getLocation());
        $stmt->bindValue('stage', $project->getStage());
        $stmt->bindValue('category', $project->getCategory());
        $stmt->bindValue('fee', $project->getFee());
        $stmt->bindValue('startDate', $project->getStartDate()->format('Y-m-d'));
        $stmt->bindValue('details', $project->getDetails());
        $stmt->bindValue('creatorId', $project->getCreator()->getId());
        $stmt->execute();

        $project->setId($this->findByCode($project->getCode())->getId());
    }

    /**
     * Find a project by its code.
     *
     * @param string $code
     * @return Project|null
     */
    public function findByCode(string $code): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Execute a stored procedure to update a project.
     */
    public function updateProjectWithSP(Project $project): void
    {
        $sql = 'CALL update_project(:projectId, :code, :name, :location, :stage, :category, :fee, :startDate, :details, :creatorId)';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('projectId', $project->getId());
        $stmt->bindValue('code', $project->getCode());
        $stmt->bindValue('name', $project->getName());
        $stmt->bindValue('location', $project->getLocation());
        $stmt->bindValue('stage', $project->getStage());
        $stmt->bindValue('category', $project->getCategory());
        $stmt->bindValue('fee', $project->getFee());
        $stmt->bindValue('startDate', $project->getStartDate()->format('Y-m-d'));
        $stmt->bindValue('details', $project->getDetails());
        $stmt->bindValue('creatorId', $project->getCreator()->getId());
        $stmt->execute();
    }

    /**
     * Execute a stored procedure to delete a project.
     */
    public function deleteProjectWithSP(Project $project): void
    {
        $sql = 'CALL delete_project(:projectId)';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('projectId', $project->getId());
        $stmt->execute();
    }
}
