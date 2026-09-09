<?php

declare(strict_types=1);

namespace App\Domain\Security;

use App\Domain\Enum\TenantRole;

final class RolePermissionMap
{
    /**
     * @return array<Permission>
     */
    public static function getPermissions(TenantRole $role): array
    {
        return match ($role) {
            TenantRole::OWNER => [
                Permission::MANAGE_TENANT,
                Permission::MANAGE_TRAINERS,
                Permission::VIEW_ALL_STUDENTS,
                Permission::MANAGE_ALL_STUDENTS,
                Permission::VIEW_ASSIGNED_STUDENTS,
                Permission::MANAGE_ASSIGNED_STUDENTS,
                Permission::MANAGE_FINANCES,
                Permission::VIEW_FINANCES,
                Permission::VIEW_EXERCISES,
                Permission::MANAGE_EXERCISES,
                Permission::VIEW_WORKOUT_TEMPLATES,
                Permission::MANAGE_WORKOUT_TEMPLATES,
                Permission::VIEW_WORKOUT_PLANS,
                Permission::MANAGE_WORKOUT_PLANS,
                Permission::VIEW_WORKOUT_SESSIONS,
                Permission::MANAGE_WORKOUT_SESSIONS,
                Permission::VIEW_ASSESSMENTS,
                Permission::MANAGE_ASSESSMENTS,
                Permission::VIEW_METRICS,
                Permission::MANAGE_METRICS,
                Permission::VIEW_ANALYTICS,
            ],
            TenantRole::ADMIN => [
                Permission::MANAGE_TENANT,
                Permission::MANAGE_TRAINERS,
                Permission::VIEW_ALL_STUDENTS,
                Permission::MANAGE_ALL_STUDENTS,
                Permission::MANAGE_FINANCES,
                Permission::VIEW_FINANCES,
                Permission::VIEW_EXERCISES,
                Permission::MANAGE_EXERCISES,
                Permission::VIEW_WORKOUT_TEMPLATES,
                Permission::MANAGE_WORKOUT_TEMPLATES,
                Permission::VIEW_WORKOUT_PLANS,
                Permission::MANAGE_WORKOUT_PLANS,
                Permission::VIEW_WORKOUT_SESSIONS,
                Permission::MANAGE_WORKOUT_SESSIONS,
                Permission::VIEW_ASSESSMENTS,
                Permission::MANAGE_ASSESSMENTS,
                Permission::VIEW_METRICS,
                Permission::MANAGE_METRICS,
                Permission::VIEW_ANALYTICS,
            ],
            TenantRole::MANAGER => [
                Permission::VIEW_ALL_STUDENTS,
                Permission::MANAGE_ALL_STUDENTS,
                Permission::VIEW_FINANCES,
                Permission::VIEW_EXERCISES,
                Permission::MANAGE_EXERCISES,
                Permission::VIEW_WORKOUT_TEMPLATES,
                Permission::MANAGE_WORKOUT_TEMPLATES,
                Permission::VIEW_WORKOUT_PLANS,
                Permission::MANAGE_WORKOUT_PLANS,
                Permission::VIEW_WORKOUT_SESSIONS,
                Permission::MANAGE_WORKOUT_SESSIONS,
                Permission::VIEW_ASSESSMENTS,
                Permission::MANAGE_ASSESSMENTS,
                Permission::VIEW_METRICS,
                Permission::MANAGE_METRICS,
                Permission::VIEW_ANALYTICS,
            ],
            TenantRole::TRAINER => [
                Permission::VIEW_ASSIGNED_STUDENTS,
                Permission::MANAGE_ASSIGNED_STUDENTS,
                Permission::VIEW_EXERCISES,
                Permission::MANAGE_EXERCISES,
                Permission::VIEW_WORKOUT_TEMPLATES,
                Permission::MANAGE_WORKOUT_TEMPLATES,
                Permission::VIEW_WORKOUT_PLANS,
                Permission::MANAGE_WORKOUT_PLANS,
                Permission::VIEW_WORKOUT_SESSIONS,
                Permission::MANAGE_WORKOUT_SESSIONS,
                Permission::VIEW_ASSESSMENTS,
                Permission::MANAGE_ASSESSMENTS,
                Permission::VIEW_METRICS,
                Permission::MANAGE_METRICS,
                Permission::VIEW_ANALYTICS,
            ],
            TenantRole::ASSISTANT => [
                Permission::VIEW_ASSIGNED_STUDENTS,
                Permission::VIEW_EXERCISES,
                Permission::VIEW_WORKOUT_TEMPLATES,
                Permission::VIEW_WORKOUT_PLANS,
                Permission::VIEW_WORKOUT_SESSIONS,
                Permission::VIEW_ASSESSMENTS,
                Permission::VIEW_METRICS,
                Permission::VIEW_ANALYTICS,
            ],
            TenantRole::FINANCE => [
                Permission::MANAGE_FINANCES,
                Permission::VIEW_FINANCES,
            ],
            TenantRole::STUDENT => [
                Permission::VIEW_OWN_STUDENT_PROFILE,
                Permission::EDIT_OWN_STUDENT_PROFILE,
                Permission::VIEW_EXERCISES,
                Permission::VIEW_WORKOUT_PLANS,
                Permission::VIEW_WORKOUT_SESSIONS,
                Permission::MANAGE_WORKOUT_SESSIONS,
                Permission::VIEW_ASSESSMENTS,
                Permission::VIEW_METRICS,
                Permission::MANAGE_METRICS,
                Permission::VIEW_ANALYTICS,
            ],
        };
    }

    public static function hasPermission(TenantRole $role, Permission $permission): bool
    {
        return in_array($permission, self::getPermissions($role), true);
    }
}
