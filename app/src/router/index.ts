import { useAuthStore } from '@/stores/auth';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/modules/auth/pages/LoginPage.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/select-tenant',
    name: 'select-tenant',
    component: () => import('@/modules/tenants/pages/SelectTenantPage.vue'),
    meta: { requiresAuth: true },
  },
  // Rotas do Personal / Assessoria
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/analytics',
    name: 'analytics',
    component: () => import('@/modules/analytics/pages/AnalyticsPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/students',
    name: 'students',
    component: () => import('@/modules/students/pages/StudentListPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/students/:id',
    name: 'student-detail',
    component: () => import('@/modules/students/pages/StudentDetailPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/trainers',
    name: 'trainers',
    component: () => import('@/modules/trainers/pages/TrainerListPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/workouts',
    name: 'workouts',
    component: () => import('@/modules/workouts/pages/TemplateListPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/workouts/builder',
    name: 'workout-builder',
    component: () => import('@/modules/workouts/pages/TemplateBuilderPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/workouts/plans/:id',
    name: 'workout-plan-detail',
    component: () => import('@/modules/workouts/pages/StudentWorkoutPlanDetailPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/exercises',
    name: 'exercises',
    component: () => import('@/modules/exercises/pages/ExerciseListPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/assessments',
    name: 'assessments',
    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/anamnesis',
    name: 'anamnesis',
    component: () => import('@/modules/anamnesis/pages/AnamnesisTemplateListPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/goals',
    name: 'goals',
    component: () => import('@/modules/goals/pages/GoalOverviewPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/follow-up',
    name: 'follow-up',
    component: () => import('@/modules/follow-up/pages/FollowUpPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/financial',
    name: 'financial',
    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN', 'TRAINER'] },
  },
  {
    path: '/saas-billing',
    name: 'saas-billing',
    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, roles: ['OWNER', 'ADMIN'] },
  },
  {
    path: '/settings/tenant',
    name: 'tenant-settings',
    component: () => import('@/modules/tenants/pages/TenantSettingsPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'settings',
    redirect: '/settings/tenant',
  },

  // Rotas de Treino ao Vivo (Personal ou Aluno)
  {
    path: '/workouts/live/:id',
    name: 'live-workout',
    component: () => import('@/modules/workouts/pages/LiveWorkoutPage.vue'),
    meta: { requiresAuth: true },
  },

  // Rotas do Aluno
  {
    path: '/portal/home',
    name: 'student-home',
    component: () => import('@/modules/student-portal/pages/StudentHomePage.vue'),
    meta: { requiresAuth: true, roles: ['STUDENT'] },
  },
  {
    path: '/portal/workout',
    name: 'student-workout',
    component: () => import('@/modules/student-portal/pages/StudentHomePage.vue'),
    meta: { requiresAuth: true, roles: ['STUDENT'] },
  },
  {
    path: '/portal/workout/live/:id',
    name: 'student-live-workout',
    component: () => import('@/modules/workouts/pages/LiveWorkoutPage.vue'),
    meta: { requiresAuth: true, roles: ['STUDENT'] },
  },
  {
    path: '/portal/history',
    name: 'student-history',
    component: () => import('@/modules/workouts/pages/WorkoutHistoryPage.vue'),
    meta: { requiresAuth: true, roles: ['STUDENT'] },
  },
  {
    path: '/portal/progress',
    name: 'student-progress',
    component: () => import('@/modules/student-portal/pages/StudentProgressPage.vue'),
    meta: { requiresAuth: true, roles: ['STUDENT'] },
  },

  // Rotas Platform Admin
  {
    path: '/admin/metrics',
    name: 'admin-metrics',
    component: () => import('@/modules/admin/pages/AdminDashboardPage.vue'),
    meta: { requiresAuth: true, platformAdminOnly: true },
  },

  // Rota Fallback 404
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore();

  // Inicializa sessão se houver token salvo
  if (authStore.token && !authStore.user) {
    await authStore.fetchMe();
  }

  // Rotas exclusivas de visitantes (ex: login)
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    if (authStore.isStudent) {
      return next('/portal/home');
    }
    if (authStore.isPlatformAdmin && !authStore.activeMembership) {
      return next('/admin/metrics');
    }
    return next('/dashboard');
  }

  // Rotas que exigem autenticação
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login');
  }

  // Validação de Platform Admin
  if (to.meta.platformAdminOnly && !authStore.isPlatformAdmin) {
    return next('/dashboard');
  }

  // Validação de papéis de tenant
  if (to.meta.roles && Array.isArray(to.meta.roles)) {
    const role = authStore.activeMembership?.role;
    if (role && !to.meta.roles.includes(role)) {
      if (role === 'STUDENT') {
        return next('/portal/home');
      }
      return next('/dashboard');
    }
  }

  next();
});
