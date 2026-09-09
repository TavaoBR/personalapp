<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\AnamnesisQuestion;
use App\Domain\Entity\AnamnesisTemplate;
use App\Domain\Entity\AnamnesisTemplateVersion;
use App\Domain\Entity\StudentAnamnesis;
use App\Domain\Entity\StudentAnamnesisAnswer;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Infrastructure\Repository\AnamnesisTemplateRepository;
use App\Infrastructure\Repository\AnamnesisTemplateVersionRepository;
use App\Infrastructure\Repository\StudentAnamnesisRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnamnesisService
{
    private const VALID_QUESTION_TYPES = [
        'TEXT',
        'LONG_TEXT',
        'NUMBER',
        'DATE',
        'YES_NO',
        'SINGLE_SELECT',
        'MULTI_SELECT',
        'SCALE',
    ];

    public function __construct(
        private readonly AnamnesisTemplateRepository $templateRepository,
        private readonly AnamnesisTemplateVersionRepository $versionRepository,
        private readonly StudentAnamnesisRepository $anamnesisRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param array<array<string, mixed>> $questionsData
     */
    public function createTemplate(
        Tenant $tenant,
        string $title,
        ?string $description = null,
        array $questionsData = []
    ): AnamnesisTemplate {
        if (trim($title) === '') {
            throw new InvalidArgumentException('O título do template é obrigatório.');
        }

        $template = new AnamnesisTemplate($tenant, $title, $description);
        $version = new AnamnesisTemplateVersion($template, 1, 'PUBLISHED');
        $template->addVersion($version);

        $orderIndex = 0;
        foreach ($questionsData as $qData) {
            $question = $this->buildQuestion($version, $qData, $orderIndex++);
            $version->addQuestion($question);
        }

        $this->templateRepository->save($template);

        return $template;
    }

    /**
     * @param array<array<string, mixed>> $questionsData
     */
    public function createVersion(
        Tenant $tenant,
        string $templateId,
        array $questionsData = []
    ): AnamnesisTemplateVersion {
        $template = $this->templateRepository->findByIdAndTenant($templateId, $tenant);
        if (!$template) {
            throw new NotFoundHttpException('Template de anamnese não encontrado.');
        }

        $latestVersion = $this->versionRepository->findLatestVersion($template);
        $nextVersionNumber = $latestVersion ? $latestVersion->getVersionNumber() + 1 : 1;

        $version = new AnamnesisTemplateVersion($template, $nextVersionNumber, 'PUBLISHED');
        $template->addVersion($version);

        $orderIndex = 0;
        foreach ($questionsData as $qData) {
            $question = $this->buildQuestion($version, $qData, $orderIndex++);
            $version->addQuestion($question);
        }

        $template->touch();
        $this->versionRepository->save($version);

        return $version;
    }

    public function getTemplate(Tenant $tenant, string $templateId): ?AnamnesisTemplate
    {
        return $this->templateRepository->findByIdAndTenant($templateId, $tenant);
    }

    /**
     * @return array<AnamnesisTemplate>
     */
    public function listTemplates(Tenant $tenant, ?string $status = null): array
    {
        return $this->templateRepository->findByTenant($tenant, $status);
    }

    /**
     * @param array<array<string, mixed>> $answersData
     */
    public function submitAnamnesis(
        Tenant $tenant,
        StudentProfile $student,
        string $versionId,
        array $answersData,
        ?string $notes = null,
        ?DateTimeImmutable $answeredAt = null
    ): StudentAnamnesis {
        $version = $this->versionRepository->findById($versionId);
        if (!$version || $version->getAnamnesisTemplate()->getTenant()->getId() !== $tenant->getId()) {
            throw new NotFoundHttpException('Versão do template de anamnese não encontrada.');
        }

        $anamnesis = new StudentAnamnesis(
            tenant: $tenant,
            student: $student,
            templateVersion: $version,
            answeredAt: $answeredAt ?? new DateTimeImmutable(),
            notes: $notes
        );

        // Index existing questions by ID
        $questionsMap = [];
        foreach ($version->getQuestions() as $q) {
            $questionsMap[$q->getId()] = $q;
        }

        foreach ($answersData as $ans) {
            $questionId = $ans['questionId'] ?? null;
            $question = null;
            $questionText = $ans['questionText'] ?? null;

            if ($questionId && isset($questionsMap[$questionId])) {
                $question = $questionsMap[$questionId];
                $questionText = $question->getQuestionText();
            }

            if (!$questionText && $question) {
                $questionText = $question->getQuestionText();
            }

            if (!$questionText) {
                continue;
            }

            $rawValue = $ans['value'] ?? null;
            $answerValue = is_array($rawValue) ? json_encode($rawValue, JSON_UNESCAPED_UNICODE) : ($rawValue !== null ? (string)$rawValue : null);

            $answerEntity = new StudentAnamnesisAnswer(
                studentAnamnesis: $anamnesis,
                questionTextSnapshot: $questionText,
                question: $question,
                answerValue: $answerValue
            );

            $anamnesis->addAnswer($answerEntity);
        }

        $this->anamnesisRepository->save($anamnesis);

        return $anamnesis;
    }

    /**
     * @return array<StudentAnamnesis>
     */
    public function getStudentAnamneses(Tenant $tenant, StudentProfile $student): array
    {
        return $this->anamnesisRepository->findByStudent($student, $tenant);
    }

    public function getAnamnesis(Tenant $tenant, string $anamnesisId): ?StudentAnamnesis
    {
        return $this->anamnesisRepository->findByIdAndTenant($anamnesisId, $tenant);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildQuestion(AnamnesisTemplateVersion $version, array $data, int $defaultOrder): AnamnesisQuestion
    {
        $text = (string)($data['text'] ?? $data['questionText'] ?? '');
        if (trim($text) === '') {
            throw new InvalidArgumentException('O texto da pergunta não pode ser vazio.');
        }

        $type = strtoupper(trim((string)($data['type'] ?? $data['questionType'] ?? 'TEXT')));
        if (!in_array($type, self::VALID_QUESTION_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de pergunta inválido: {$type}");
        }

        $required = (bool)($data['required'] ?? false);
        $orderIndex = isset($data['orderIndex']) ? (int)$data['orderIndex'] : $defaultOrder;
        $configuration = isset($data['configuration']) && is_array($data['configuration']) ? $data['configuration'] : null;

        return new AnamnesisQuestion(
            templateVersion: $version,
            questionText: $text,
            questionType: $type,
            required: $required,
            orderIndex: $orderIndex,
            configuration: $configuration
        );
    }
}
