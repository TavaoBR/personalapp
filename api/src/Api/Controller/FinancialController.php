<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\FinancialService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\CoachingPlan;
use App\Domain\Entity\StudentEnrollment;
use App\Domain\Entity\StudentPayment;
use App\Domain\Entity\User;
use App\Domain\Enum\BillingPeriod;
use App\Domain\Enum\EnrollmentStatus;
use App\Domain\Enum\PaymentStatus;
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

final class FinancialController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly FinancialService $financialService,
        private readonly StudentProfileRepository $studentRepository
    ) {
    }

    #[Route('/api/finances/coaching-plans', name: 'api_finances_coaching_plans_list', methods: ['GET'])]
    public function listCoachingPlans(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FINANCES);

        $plans = $this->financialService->listCoachingPlans($tenant);
        $data = array_map(fn (CoachingPlan $p) => $this->serializePlan($p), $plans);

        return new JsonResponse(['plans' => $data]);
    }

    #[Route('/api/finances/coaching-plans', name: 'api_finances_coaching_plans_create', methods: ['POST'])]
    public function createCoachingPlan(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FINANCES);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || !isset($data['price'])) {
            throw new BadRequestHttpException('Nome e preço são obrigatórios.');
        }

        $price = (float) $data['price'];
        $period = BillingPeriod::tryFrom((string) ($data['billing_period'] ?? 'MONTHLY')) ?? BillingPeriod::MONTHLY;
        $durationMonths = isset($data['duration_months']) ? (int) $data['duration_months'] : 1;
        $description = isset($data['description']) ? (string) $data['description'] : null;

        $plan = $this->financialService->createCoachingPlan(
            tenant: $tenant,
            name: $name,
            price: $price,
            billingPeriod: $period,
            durationMonths: $durationMonths,
            description: $description
        );

        return new JsonResponse($this->serializePlan($plan), Response::HTTP_CREATED);
    }

    #[Route('/api/finances/coaching-plans/{id}', name: 'api_finances_coaching_plans_detail', methods: ['GET'])]
    public function coachingPlanDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FINANCES);

        $plan = $this->financialService->getCoachingPlan($tenant, $id);
        if ($plan === null) {
            throw new NotFoundHttpException('Plano de consultoria não encontrado.');
        }

        return new JsonResponse($this->serializePlan($plan));
    }

    #[Route('/api/students/{id}/enrollments', name: 'api_student_enrollments_list', methods: ['GET'])]
    public function listEnrollments(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FINANCES);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $enrollments = $this->financialService->listStudentEnrollments($tenant, $student);
        $data = array_map(fn (StudentEnrollment $e) => $this->serializeEnrollment($e), $enrollments);

        return new JsonResponse(['enrollments' => $data]);
    }

    #[Route('/api/students/{id}/enrollments', name: 'api_student_enrollments_create', methods: ['POST'])]
    public function createEnrollment(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FINANCES);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $planId = (string) ($data['coaching_plan_id'] ?? $data['plan_id'] ?? '');
        if ($planId === '') {
            throw new BadRequestHttpException('O ID do plano de consultoria é obrigatório.');
        }

        $customPrice = isset($data['price']) ? (float) $data['price'] : null;
        $startDate = isset($data['start_date']) ? new DateTimeImmutable((string) $data['start_date']) : null;
        $billingDay = isset($data['billing_day']) ? (int) $data['billing_day'] : 10;
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;
        $generatePayment = (bool) ($data['generate_first_payment'] ?? true);

        $enrollment = $this->financialService->enrollStudent(
            tenant: $tenant,
            student: $student,
            planId: $planId,
            customPrice: $customPrice,
            startDate: $startDate,
            billingDay: $billingDay,
            notes: $notes,
            generateFirstPayment: $generatePayment
        );

        return new JsonResponse($this->serializeEnrollment($enrollment), Response::HTTP_CREATED);
    }

    #[Route('/api/finances/enrollments/{id}', name: 'api_finances_enrollments_cancel', methods: ['PATCH'])]
    public function cancelEnrollment(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FINANCES);

        $enrollment = $this->financialService->cancelEnrollment($tenant, $id);

        return new JsonResponse($this->serializeEnrollment($enrollment));
    }

    #[Route('/api/finances/payments', name: 'api_finances_payments_list', methods: ['GET'])]
    public function listPayments(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FINANCES);

        $status = $request->query->has('status') ? PaymentStatus::tryFrom((string) $request->query->get('status')) : null;
        $student = null;
        if ($request->query->has('student_id')) {
            $student = $this->studentRepository->findByIdAndTenant((string) $request->query->get('student_id'), $tenant);
        }

        $startDate = $request->query->has('start_date') ? new DateTimeImmutable((string) $request->query->get('start_date')) : null;
        $endDate = $request->query->has('end_date') ? new DateTimeImmutable((string) $request->query->get('end_date')) : null;

        $payments = $this->financialService->listPayments(
            tenant: $tenant,
            status: $status,
            student: $student,
            startDate: $startDate,
            endDate: $endDate
        );

        $data = array_map(fn (StudentPayment $p) => $this->serializePayment($p), $payments);

        return new JsonResponse(['payments' => $data]);
    }

    #[Route('/api/finances/payments', name: 'api_finances_payments_create', methods: ['POST'])]
    public function createPayment(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FINANCES);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $studentId = (string) ($data['student_id'] ?? '');
        if ($studentId === '' || !isset($data['amount']) || !isset($data['due_date'])) {
            throw new BadRequestHttpException('Aluno, valor e data de vencimento são obrigatórios.');
        }

        $student = $this->studentRepository->findByIdAndTenant($studentId, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $amount = (float) $data['amount'];
        $dueDate = new DateTimeImmutable((string) $data['due_date']);
        $enrollmentId = isset($data['enrollment_id']) ? (string) $data['enrollment_id'] : null;
        $referencePeriod = isset($data['reference_period']) ? (string) $data['reference_period'] : null;
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        $payment = $this->financialService->createPayment(
            tenant: $tenant,
            student: $student,
            amount: $amount,
            dueDate: $dueDate,
            enrollmentId: $enrollmentId,
            referencePeriod: $referencePeriod,
            notes: $notes
        );

        return new JsonResponse($this->serializePayment($payment), Response::HTTP_CREATED);
    }

    #[Route('/api/finances/payments/{id}/pay', name: 'api_finances_payments_pay', methods: ['POST'])]
    public function markAsPaid(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FINANCES);

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $method = (string) ($data['payment_method'] ?? 'PIX');
        $paidAt = isset($data['paid_at']) ? new DateTimeImmutable((string) $data['paid_at']) : null;

        $payment = $this->financialService->markPaymentAsPaid(
            tenant: $tenant,
            paymentId: $id,
            paymentMethod: $method,
            paidAt: $paidAt
        );

        return new JsonResponse($this->serializePayment($payment));
    }

    #[Route('/api/students/{id}/payments', name: 'api_student_payments_list', methods: ['GET'])]
    public function listStudentPayments(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FINANCES);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $payments = $this->financialService->listStudentPayments($tenant, $student);
        $data = array_map(fn (StudentPayment $p) => $this->serializePayment($p), $payments);

        return new JsonResponse(['payments' => $data]);
    }

    #[Route('/api/finances/dashboard', name: 'api_finances_dashboard', methods: ['GET'])]
    public function dashboard(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FINANCES);

        $dashboard = $this->financialService->getFinancialDashboard($tenant);

        return new JsonResponse($dashboard);
    }

    #[Route('/api/me/payments', name: 'api_me_payments', methods: ['GET'])]
    public function mePayments(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);

        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $payments = $this->financialService->listStudentPayments($tenant, $student);
        $data = array_map(fn (StudentPayment $p) => $this->serializePayment($p), $payments);

        return new JsonResponse(['payments' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(CoachingPlan $p): array
    {
        return [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'price' => $p->getPrice(),
            'billing_period' => $p->getBillingPeriod()->value,
            'duration_months' => $p->getDurationMonths(),
            'status' => $p->getStatus(),
            'created_at' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEnrollment(StudentEnrollment $e): array
    {
        return [
            'id' => $e->getId(),
            'student_id' => $e->getStudent()->getId(),
            'plan_name' => $e->getCoachingPlan()->getName(),
            'price' => $e->getPrice(),
            'start_date' => $e->getStartDate()->format('Y-m-d'),
            'end_date' => $e->getEndDate()?->format('Y-m-d'),
            'billing_day' => $e->getBillingDay(),
            'status' => $e->getStatus()->value,
            'notes' => $e->getNotes(),
            'created_at' => $e->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(StudentPayment $p): array
    {
        return [
            'id' => $p->getId(),
            'student_id' => $p->getStudent()->getId(),
            'amount' => $p->getAmount(),
            'due_date' => $p->getDueDate()->format('Y-m-d'),
            'paid_at' => $p->getPaidAt()?->format(\DateTimeInterface::ATOM),
            'payment_method' => $p->getPaymentMethod(),
            'status' => $p->getStatus()->value,
            'reference_period' => $p->getReferencePeriod(),
            'notes' => $p->getNotes(),
            'created_at' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
