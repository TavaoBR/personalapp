<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\CoachingPlan;
use App\Domain\Entity\StudentEnrollment;
use App\Domain\Entity\StudentPayment;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Enum\BillingPeriod;
use App\Domain\Enum\EnrollmentStatus;
use App\Domain\Enum\PaymentStatus;
use App\Infrastructure\Repository\CoachingPlanRepository;
use App\Infrastructure\Repository\StudentEnrollmentRepository;
use App\Infrastructure\Repository\StudentPaymentRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FinancialService
{
    public function __construct(
        private readonly CoachingPlanRepository $planRepository,
        private readonly StudentEnrollmentRepository $enrollmentRepository,
        private readonly StudentPaymentRepository $paymentRepository
    ) {
    }

    public function createCoachingPlan(
        Tenant $tenant,
        string $name,
        float $price,
        BillingPeriod $billingPeriod = BillingPeriod::MONTHLY,
        int $durationMonths = 1,
        ?string $description = null
    ): CoachingPlan {
        if (trim($name) === '') {
            throw new InvalidArgumentException('O nome do plano de consultoria é obrigatório.');
        }

        $plan = new CoachingPlan(
            tenant: $tenant,
            name: $name,
            price: $price,
            billingPeriod: $billingPeriod,
            durationMonths: $durationMonths,
            description: $description
        );

        $this->planRepository->save($plan);

        return $plan;
    }

    public function getCoachingPlan(Tenant $tenant, string $id): ?CoachingPlan
    {
        return $this->planRepository->findByIdAndTenant($id, $tenant);
    }

    /**
     * @return array<CoachingPlan>
     */
    public function listCoachingPlans(Tenant $tenant, ?string $status = 'ACTIVE'): array
    {
        return $this->planRepository->findByTenant($tenant, $status);
    }

    public function enrollStudent(
        Tenant $tenant,
        StudentProfile $student,
        string $planId,
        ?float $customPrice = null,
        ?DateTimeImmutable $startDate = null,
        int $billingDay = 10,
        ?string $notes = null,
        bool $generateFirstPayment = true
    ): StudentEnrollment {
        $plan = $this->planRepository->findByIdAndTenant($planId, $tenant);
        if ($plan === null) {
            throw new NotFoundHttpException('Plano de consultoria não encontrado.');
        }

        $start = $startDate ?? new DateTimeImmutable();
        $duration = $plan->getDurationMonths();
        $endDate = $start->modify("+{$duration} months");
        $price = $customPrice ?? $plan->getPrice();

        $enrollment = new StudentEnrollment(
            tenant: $tenant,
            student: $student,
            coachingPlan: $plan,
            price: $price,
            startDate: $start,
            endDate: $endDate,
            billingDay: $billingDay,
            status: EnrollmentStatus::ACTIVE,
            notes: $notes
        );

        if ($generateFirstPayment && $price > 0) {
            // Primeiro vencimento no início ou no dia de faturamento
            $dueDay = min(28, max(1, $billingDay));
            $dueDate = new DateTimeImmutable(sprintf('%s-%02d', $start->format('Y-m'), $dueDay));
            if ($dueDate < $start) {
                $dueDate = $start->modify('+5 days');
            }

            $payment = new StudentPayment(
                tenant: $tenant,
                student: $student,
                amount: $price,
                dueDate: $dueDate,
                enrollment: $enrollment,
                status: PaymentStatus::PENDING,
                referencePeriod: $start->format('Y-m'),
                notes: 'Primeira mensalidade da matrícula'
            );

            $enrollment->addPayment($payment);
        }

        $this->enrollmentRepository->save($enrollment);

        return $enrollment;
    }

    public function cancelEnrollment(Tenant $tenant, string $enrollmentId): StudentEnrollment
    {
        $enrollment = $this->enrollmentRepository->findByIdAndTenant($enrollmentId, $tenant);
        if ($enrollment === null) {
            throw new NotFoundHttpException('Matrícula não encontrada.');
        }

        $enrollment->setStatus(EnrollmentStatus::CANCELLED);
        $this->enrollmentRepository->save($enrollment);

        return $enrollment;
    }

    /**
     * @return array<StudentEnrollment>
     */
    public function listStudentEnrollments(Tenant $tenant, StudentProfile $student, ?EnrollmentStatus $status = null): array
    {
        return $this->enrollmentRepository->findByStudentAndTenant($student, $tenant, $status);
    }

    public function getEnrollmentById(Tenant $tenant, string $id): ?StudentEnrollment
    {
        return $this->enrollmentRepository->findByIdAndTenant($id, $tenant);
    }

    public function createPayment(
        Tenant $tenant,
        StudentProfile $student,
        float $amount,
        DateTimeImmutable $dueDate,
        ?string $enrollmentId = null,
        ?string $referencePeriod = null,
        ?string $notes = null
    ): StudentPayment {
        $enrollment = null;
        if ($enrollmentId !== null && trim($enrollmentId) !== '') {
            $enrollment = $this->enrollmentRepository->findByIdAndTenant($enrollmentId, $tenant);
            if ($enrollment === null) {
                throw new NotFoundHttpException('Matrícula não encontrada.');
            }
        }

        $payment = new StudentPayment(
            tenant: $tenant,
            student: $student,
            amount: $amount,
            dueDate: $dueDate,
            enrollment: $enrollment,
            status: PaymentStatus::PENDING,
            referencePeriod: $referencePeriod,
            notes: $notes
        );

        $this->paymentRepository->save($payment);

        return $payment;
    }

    public function markPaymentAsPaid(
        Tenant $tenant,
        string $paymentId,
        string $paymentMethod = 'PIX',
        ?DateTimeImmutable $paidAt = null
    ): StudentPayment {
        $payment = $this->paymentRepository->findByIdAndTenant($paymentId, $tenant);
        if ($payment === null) {
            throw new NotFoundHttpException('Fatura/pagamento não encontrado.');
        }

        $payment->markAsPaid($paymentMethod, $paidAt);
        $this->paymentRepository->save($payment);

        return $payment;
    }

    /**
     * @return array<StudentPayment>
     */
    public function listPayments(
        Tenant $tenant,
        ?PaymentStatus $status = null,
        ?StudentProfile $student = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null
    ): array {
        return $this->paymentRepository->findFiltered($tenant, $status, $student, $startDate, $endDate);
    }

    public function getPaymentById(Tenant $tenant, string $id): ?StudentPayment
    {
        return $this->paymentRepository->findByIdAndTenant($id, $tenant);
    }

    /**
     * @return array<StudentPayment>
     */
    public function listStudentPayments(Tenant $tenant, StudentProfile $student): array
    {
        return $this->paymentRepository->findByStudentAndTenant($student, $tenant);
    }

    /**
     * Dashboard Financeiro consolidado com indicadores de faturamento e vencimentos.
     *
     * @return array<string, mixed>
     */
    public function getFinancialDashboard(Tenant $tenant): array
    {
        $now = new DateTimeImmutable();
        $currentMonth = $now->format('Y-m');
        $allPayments = $this->paymentRepository->findFiltered($tenant);

        $totalRevenueThisMonth = 0.0;
        $pendingRevenueThisMonth = 0.0;
        $overdueRevenue = 0.0;
        $overdueCount = 0;
        $paidCount = 0;
        $upcomingPayments = [];
        $overduePayments = [];

        $today = new DateTimeImmutable($now->format('Y-m-d'));
        $in15Days = $today->modify('+15 days');

        foreach ($allPayments as $p) {
            $isPaid = $p->getStatus() === PaymentStatus::PAID;
            $dueDate = $p->getDueDate();

            if ($isPaid) {
                $paidCount++;
                if ($p->getPaidAt() && $p->getPaidAt()->format('Y-m') === $currentMonth) {
                    $totalRevenueThisMonth += $p->getAmount();
                }
            } else {
                // Pagamento não realizado
                if ($dueDate < $today) {
                    // Vencido
                    $overdueRevenue += $p->getAmount();
                    $overdueCount++;
                    $overduePayments[] = [
                        'id' => $p->getId(),
                        'student_id' => $p->getStudent()->getId(),
                        'student_name' => $p->getStudent()->getUser()->getName(),
                        'amount' => $p->getAmount(),
                        'due_date' => $dueDate->format('Y-m-d'),
                        'days_overdue' => (int) $today->diff($dueDate)->days,
                    ];
                } else {
                    // A vencer
                    if ($dueDate->format('Y-m') === $currentMonth) {
                        $pendingRevenueThisMonth += $p->getAmount();
                    }
                    if ($dueDate >= $today && $dueDate <= $in15Days) {
                        $upcomingPayments[] = [
                            'id' => $p->getId(),
                            'student_id' => $p->getStudent()->getId(),
                            'student_name' => $p->getStudent()->getUser()->getName(),
                            'amount' => $p->getAmount(),
                            'due_date' => $dueDate->format('Y-m-d'),
                        ];
                    }
                }
            }
        }

        $activeEnrollments = $this->enrollmentRepository->countActiveByTenant($tenant);
        $totalEvaluated = $paidCount + $overdueCount;
        $defaultRate = $totalEvaluated > 0 ? round(($overdueCount / $totalEvaluated) * 100, 1) : 0.0;

        return [
            'month' => $currentMonth,
            'active_students_count' => $activeEnrollments,
            'total_revenue_month' => round($totalRevenueThisMonth, 2),
            'pending_revenue_month' => round($pendingRevenueThisMonth, 2),
            'overdue_revenue' => round($overdueRevenue, 2),
            'default_rate_percentage' => $defaultRate,
            'upcoming_payments' => $upcomingPayments,
            'overdue_payments' => $overduePayments,
        ];
    }
}
