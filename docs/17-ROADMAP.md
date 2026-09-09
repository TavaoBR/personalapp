# 17 — Roadmap Geral

## P0 — Fundação

1. Estrutura `api/` + `app/`.
2. Docker + Nginx para ambiente local.
3. MySQL real e Doctrine Migrations.
4. Symfony 7 + PHP 8.4 no backend.
5. Vue 3 + TypeScript + Pinia + Tailwind + Flowbite no frontend.
6. User/Tenant/Membership.
7. StudentProfile/TrainerProfile.
8. TenantContext.
9. Authentication.
10. Authorization.
11. Isolation tests.
12. Abstração de storage + LocalFileStorage em volume persistente.
13. Symfony Messenger com Doctrine transport.

## P1 — Núcleo de treino

14. Exercícios.
15. Templates.
16. StudentWorkoutPlan.
17. Versionamento.
18. WorkoutSession.
19. SetExecution.
20. Histórico.

## P2 — Acompanhamento

21. Avaliações.
22. Métricas.
23. Analytics.
24. Anamnese configurável.
25. Metas.
26. FollowUpPlan.
27. Check-ins.
28. Timeline.

## P3 — Operação e produção

29. Financeiro aluno.
30. SaaS billing.
31. Branding.
32. Platform Admin.
33. Hardening do storage local e backups.
34. Cache somente onde houver ganho medido.
35. Jobs/filas e retry policy.
36. Observabilidade.
37. Avaliar Redis somente se o volume justificar.
38. Avaliar S3/R2 somente se custo, escala ou disponibilidade justificarem.
