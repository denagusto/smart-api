<?php

namespace App\Request\Project;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CreateRequest
{
    #[Assert\NotBlank(message: "Name is required.")]
    #[Assert\Length(max: 200, maxMessage: "Name cannot be longer than 200 characters.")]
    private $name;

    #[Assert\NotBlank(message: "Location is required.")]
    #[Assert\Length(max: 500, maxMessage: "Location cannot be longer than 500 characters.")]
    private $location;

    #[Assert\NotBlank(message: "Stage is required.")]
    #[Assert\Choice(choices: ["Concept", "Design & Documentation", "Pre-Construction", "Construction"], message: "Choose a valid category.")]
    private $stage;

    #[Assert\NotBlank(message: "Category is required.")]
    #[Assert\Choice(choices: ["Education", "Health", "Office", "Others"], message: "Choose a valid category.")]
    private $category;

    #[Assert\Callback([CreateRequest::class, "validateCategory"])]
    private $categoryText; // Holds free text for "Others" category

    #[Assert\Type(type: "numeric", message: "Fee must be a number.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Fee must be greater than or equal to 0.")]
    private $fee;

    #[Assert\NotBlank(message: "Start Date is required.")]
    // #[Assert\Date(message: "Start Date must be a valid date.")]
    #[Assert\Callback([CreateRequest::class, "validateStartDate"])]
    private $startDate;

    #[Assert\NotBlank(message: "Details are required.")]
    #[Assert\Length(max: 2000, maxMessage: "Details cannot be longer than 2000 characters.")]
    private $details;

    public static function validateStartDate($startDate, ExecutionContextInterface $context)
    {
        $project = $context->getObject(); // Get the CreateRequest object

        if (in_array($project->getStage(), ['Concept', 'Design & Documentation', 'Pre-Construction'])) {
            $today = new \DateTime();
            if ($startDate < $today) {
                $context->buildViolation('The Start Date must be in the future for Concept, Design, or PreConstruction stages.')
                    ->atPath('startDate')
                    ->addViolation();
            }
        }
    }

    public static function validateCategory($category, ExecutionContextInterface $context)
    {
        $project = $context->getObject(); // Get the CreateRequest object

        if ($project->getCategory() === 'Others' && empty($project->getCategoryText())) {
            $context->buildViolation('Please specify the category if "Others" is selected.')
                ->atPath('categoryText')
                ->addViolation();
        }
    }

    // Getters and Setters...

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getStage(): ?string
    {
        return $this->stage;
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getFee(): ?string
    {
        return $this->fee;
    }

    public function setFee(?string $fee): void
    {
        $this->fee = $fee;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): void
    {
        $this->details = $details;
    }

    public function getCategoryText(): ?string
    {
        return $this->categoryText;
    }

    public function setCategoryText(?string $categoryText): void
    {
        $this->categoryText = $categoryText;
    }
}
