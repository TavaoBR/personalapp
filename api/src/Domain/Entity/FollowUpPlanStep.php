<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'follow_up_plan_steps')]
#[ORM\Index(name: 'idx_follow_up_step_version', columns: ['follow_up_plan_version_id'])]
class FollowUpPlanStep
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: FollowUpPlanVersion::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'follow_up_plan_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FollowUpPlanVersion $version;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'string', length: 30)]
    private string $stepType; // ANAMNESIS, ASSESSMENT, WORKOUT_REVIEW, CHECK_IN, GOAL_REVIEW, CUSTOM

    #[ORM\Column(type: 'integer')]
    private int $dayOffset = 1;

    #[ORM\Column(type: 'integer')]
    private int $orderIndex = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $configuration = null;

    /**
     * @param array<string, mixed>|null $configuration
     */
    public function __construct(
        FollowUpPlanVersion $version,
        string $title,
        string $stepType,
        int $dayOffset = 1,
        int $orderIndex = 0,
        ?string $description = null,
        ?array $configuration = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->version = $version;
        $this->title = trim($title);
        $this->stepType = strtoupper(trim($stepType));
        $this->dayOffset = max(1, $dayOffset);
        $this->orderIndex = $orderIndex;
        $this->description = $description;
        $this->configuration = $configuration;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getVersion(): FollowUpPlanVersion
    {
        return $this->version;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStepType(): string
    {
        return $this->stepType;
    }

    public function getDayOffset(): int
    {
        return $this->dayOffset;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }
}
