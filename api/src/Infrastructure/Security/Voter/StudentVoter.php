<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Voter;

use App\Application\Security\TenantAuthorizationService;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StudentVoter extends Voter
{
    public const VIEW = 'STUDENT_VIEW';
    public const EDIT = 'STUDENT_EDIT';

    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof StudentProfile;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var StudentProfile $student */
        $student = $subject;

        return $this->authorizationService->canAccessStudent($user, $student);
    }
}
