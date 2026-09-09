<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\FollowUpPlanVersionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FollowUpPlanVersionRepository::class)]
#[ORM\Table(name: 'follow_up_plan_versions')]
#[ORM\Index(name: 'idx_follow_up_ver_lookup', columns: ['follow_up_plan_template_id', 'version_number'])]
class FollowUpPlanVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: FollowUpPlanTemplate::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(name: 'follow_up_plan_template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FollowUpPlanTemplate $template;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // 'PUBLISHED', 'ARCHIVED'

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    /**
     * @var Collection<int, FollowUpPlanStep>
     */
    #[ORM\OneToMany(targetEntity: FollowUpPlanStep::class, mappedBy: 'version', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dayOffset' => 'ASC', 'orderIndex' => 'ASC'])]
    private Collection $steps;

    public function __construct(
        FollowUpPlanTemplate $template,
        int $versionNumber,
        string $status = 'PUBLISHED',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->template = $template;
        $this->versionNumber = $versionNumber;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        if ($status === 'PUBLISHED') {
            $this->publishedAt = new DateTimeImmutable();
        }
        $this->steps = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTemplate(): FollowUpPlanTemplate
    {
        return $this->template;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        if ($status === 'PUBLISHED' && $this->publishedAt === null) {
            $this->publishedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * @return Collection<int, FollowUpPlanStep>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(FollowUpPlanStep $step): self
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);
        }
        return $this;
    }
}
