<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\AnamnesisTemplateVersionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnamnesisTemplateVersionRepository::class)]
#[ORM\Table(name: 'anamnesis_template_versions')]
#[ORM\Index(name: 'idx_anamnesis_ver_lookup', columns: ['anamnesis_template_id', 'version_number'])]
class AnamnesisTemplateVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: AnamnesisTemplate::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(name: 'anamnesis_template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AnamnesisTemplate $anamnesisTemplate;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // 'PUBLISHED', 'ARCHIVED'

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    /**
     * @var Collection<int, AnamnesisQuestion>
     */
    #[ORM\OneToMany(targetEntity: AnamnesisQuestion::class, mappedBy: 'templateVersion', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $questions;

    public function __construct(
        AnamnesisTemplate $anamnesisTemplate,
        int $versionNumber,
        string $status = 'PUBLISHED',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->anamnesisTemplate = $anamnesisTemplate;
        $this->versionNumber = $versionNumber;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        if ($status === 'PUBLISHED') {
            $this->publishedAt = new DateTimeImmutable();
        }
        $this->questions = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAnamnesisTemplate(): AnamnesisTemplate
    {
        return $this->anamnesisTemplate;
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
     * @return Collection<int, AnamnesisQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(AnamnesisQuestion $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
        }
        return $this;
    }
}
