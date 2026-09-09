<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\AnamnesisService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\AnamnesisQuestion;
use App\Domain\Entity\AnamnesisTemplate;
use App\Domain\Entity\AnamnesisTemplateVersion;
use App\Domain\Entity\StudentAnamnesis;
use App\Domain\Entity\StudentAnamnesisAnswer;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\StudentProfileRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AnamnesisController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly AnamnesisService $anamnesisService,
        private readonly StudentProfileRepository $studentRepository
    ) {
    }

    #[Route('/api/anamnesis-templates', name: 'api_anamnesis_templates_list', methods: ['GET'])]
    public function listTemplates(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANAMNESIS);

        $templates = $this->anamnesisService->listTemplates($tenant);

        $data = array_map(function (AnamnesisTemplate $t) {
            $latestVersion = $t->getVersions()->first();
            return [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'description' => $t->getDescription(),
                'status' => $t->getStatus(),
                'latest_version' => $latestVersion ? $latestVersion->getVersionNumber() : 1,
                'created_at' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $t->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $templates);

        return new JsonResponse(['templates' => $data]);
    }

    #[Route('/api/anamnesis-templates', name: 'api_anamnesis_templates_create', methods: ['POST'])]
    public function createTemplate(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_ANAMNESIS);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $title = trim((string) ($data['title'] ?? ''));
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $questions = (array) ($data['questions'] ?? []);

        if ($title === '') {
            throw new BadRequestHttpException('O título do template é obrigatório.');
        }

        $template = $this->anamnesisService->createTemplate(
            tenant: $tenant,
            title: $title,
            description: $description,
            questionsData: $questions
        );

        return new JsonResponse($this->serializeTemplate($template), Response::HTTP_CREATED);
    }

    #[Route('/api/anamnesis-templates/{id}', name: 'api_anamnesis_templates_detail', methods: ['GET'])]
    public function templateDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANAMNESIS);

        $template = $this->anamnesisService->getTemplate($tenant, $id);
        if ($template === null) {
            throw new NotFoundHttpException('Template de anamnese não encontrado.');
        }

        return new JsonResponse($this->serializeTemplate($template));
    }

    #[Route('/api/anamnesis-templates/{id}/versions', name: 'api_anamnesis_templates_create_version', methods: ['POST'])]
    public function createVersion(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_ANAMNESIS);

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $questions = (array) ($data['questions'] ?? []);

        $version = $this->anamnesisService->createVersion(
            tenant: $tenant,
            templateId: $id,
            questionsData: $questions
        );

        return new JsonResponse($this->serializeVersion($version), Response::HTTP_CREATED);
    }

    #[Route('/api/students/{id}/anamneses', name: 'api_student_anamneses_list', methods: ['GET'])]
    public function listStudentAnamneses(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANAMNESIS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $list = $this->anamnesisService->getStudentAnamneses($tenant, $student);

        $data = array_map(fn (StudentAnamnesis $a) => $this->serializeAnamnesisSummary($a), $list);

        return new JsonResponse(['anamneses' => $data]);
    }

    #[Route('/api/students/{id}/anamneses', name: 'api_student_anamneses_create', methods: ['POST'])]
    public function submitAnamnesis(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::MANAGE_ANAMNESIS);
            $this->authorizationService->assertCanAccessStudent($user, $student);
        } else {
            // Student can only answer their own
            if ($student->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Aluno não encontrado.');
            }
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $versionId = (string) ($data['version_id'] ?? $data['template_version_id'] ?? '');
        if ($versionId === '') {
            throw new BadRequestHttpException('O ID da versão do template é obrigatório.');
        }

        $answers = (array) ($data['answers'] ?? []);
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;
        $answeredAt = isset($data['answered_at']) ? new DateTimeImmutable((string) $data['answered_at']) : null;

        $anamnesis = $this->anamnesisService->submitAnamnesis(
            tenant: $tenant,
            student: $student,
            versionId: $versionId,
            answersData: $answers,
            notes: $notes,
            answeredAt: $answeredAt
        );

        return new JsonResponse($this->serializeAnamnesisDetail($anamnesis), Response::HTTP_CREATED);
    }

    #[Route('/api/anamneses/{id}', name: 'api_anamneses_detail', methods: ['GET'])]
    public function anamnesisDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $anamnesis = $this->anamnesisService->getAnamnesis($tenant, $id);

        if ($anamnesis === null) {
            throw new NotFoundHttpException('Anamnese não encontrada.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANAMNESIS);
            $this->authorizationService->assertCanAccessStudent($user, $anamnesis->getStudent());
        } else {
            if ($anamnesis->getStudent()->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Anamnese não encontrada.');
            }
        }

        return new JsonResponse($this->serializeAnamnesisDetail($anamnesis));
    }

    #[Route('/api/me/anamneses', name: 'api_me_anamneses', methods: ['GET'])]
    public function meAnamneses(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $list = $this->anamnesisService->getStudentAnamneses($tenant, $student);
        $data = array_map(fn (StudentAnamnesis $a) => $this->serializeAnamnesisSummary($a), $list);

        return new JsonResponse(['anamneses' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTemplate(AnamnesisTemplate $template): array
    {
        $versions = [];
        foreach ($template->getVersions() as $v) {
            $versions[] = $this->serializeVersion($v);
        }

        return [
            'id' => $template->getId(),
            'title' => $template->getTitle(),
            'description' => $template->getDescription(),
            'status' => $template->getStatus(),
            'created_at' => $template->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $template->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'versions' => $versions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVersion(AnamnesisTemplateVersion $version): array
    {
        $questions = [];
        foreach ($version->getQuestions() as $q) {
            $questions[] = [
                'id' => $q->getId(),
                'text' => $q->getQuestionText(),
                'type' => $q->getQuestionType(),
                'required' => $q->isRequired(),
                'order_index' => $q->getOrderIndex(),
                'configuration' => $q->getConfiguration(),
            ];
        }

        return [
            'id' => $version->getId(),
            'version_number' => $version->getVersionNumber(),
            'status' => $version->getStatus(),
            'published_at' => $version->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $version->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'questions' => $questions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnamnesisSummary(StudentAnamnesis $a): array
    {
        return [
            'id' => $a->getId(),
            'student_id' => $a->getStudent()->getId(),
            'template_title' => $a->getTemplateVersion()->getAnamnesisTemplate()->getTitle(),
            'version_number' => $a->getTemplateVersion()->getVersionNumber(),
            'answered_at' => $a->getAnsweredAt()->format(\DateTimeInterface::ATOM),
            'status' => $a->getStatus(),
            'answers_count' => $a->getAnswers()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnamnesisDetail(StudentAnamnesis $a): array
    {
        $answers = [];
        foreach ($a->getAnswers() as $ans) {
            $answers[] = [
                'id' => $ans->getId(),
                'question_id' => $ans->getQuestion()?->getId(),
                'question_text' => $ans->getQuestionTextSnapshot(),
                'answer' => $ans->getAnswerValue(),
            ];
        }

        return [
            'id' => $a->getId(),
            'student_id' => $a->getStudent()->getId(),
            'template_title' => $a->getTemplateVersion()->getAnamnesisTemplate()->getTitle(),
            'version_number' => $a->getTemplateVersion()->getVersionNumber(),
            'answered_at' => $a->getAnsweredAt()->format(\DateTimeInterface::ATOM),
            'status' => $a->getStatus(),
            'notes' => $a->getNotes(),
            'answers' => $answers,
        ];
    }
}
