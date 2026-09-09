# 10 — Timeline e Versionamento

## Timeline

Eventos:

- entrada do aluno;
- criação da ficha;
- publicação de nova versão;
- treino realizado;
- avaliação;
- anamnese;
- meta;
- progresso;
- PR;
- observação;
- check-in;
- revisão;
- alteração de carga.

## StudentTimelineEvent

Funciona como índice para eventos do domínio.

Não deve duplicar todo o conteúdo original.

## Histórico x auditoria

Histórico funcional:

```text
WorkoutPlan v1
WorkoutPlan v2
```

Auditoria:

```text
quem alterou
quando
IP
mudanças
```

Manter ambos.
