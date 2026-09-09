<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'anamnesis_questions')]
#[ORM\Index(name: 'idx_anamnesis_question_version', columns: ['template_version_id'])]
class AnamnesisQuestion
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: AnamnesisTemplateVersion::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'template_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AnamnesisTemplateVersion $templateVersion;

    #[ORM\Column(type: 'text')]
    private string $questionText;

    #[ORM\Column(type: 'string', length: 30)]
    private string $questionType; // TEXT, LONG_TEXT, NUMBER, DATE, YES_NO, SINGLE_SELECT, MULTI_SELECT, SCALE

    #[ORM\Column(type: 'boolean')]
    private bool $required = false;

    #[ORM\Column(type: 'integer')]
    private int $orderIndex = 0;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $configuration = null;

    /**
     * @param array<string, mixed>|null $configuration
     */
    public function __construct(
        AnamnesisTemplateVersion $templateVersion,
        string $questionText,
        string $questionType,
        bool $required = false,
        int $orderIndex = 0,
        ?array $configuration = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->templateVersion = $templateVersion;
        $this->questionText = trim($questionText);
        $this->questionType = strtoupper(trim($questionType));
        $this->required = $required;
        $this->orderIndex = $orderIndex;
        $this->configuration = $configuration;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTemplateVersion(): AnamnesisTemplateVersion
    {
        return $this->templateVersion;
    }

    public function getQuestionText(): string
    {
        return $this->questionText;
    }

    public function getQuestionType(): string
    {
        return $this->questionType;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    /**
     * @param array<string, mixed>|null $configuration
     */
    public function setConfiguration(?array $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }
}
