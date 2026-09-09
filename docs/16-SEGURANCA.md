# 16 — Segurança

Testes obrigatórios:

```text
Personal A acessa Student B
→ 404

Student A acessa Student B
→ 404

Student altera TenantId
→ sem efeito / rejeitado

Student altera StudentId
→ sem acesso

Trainer não vinculado acessa aluno
→ bloqueado
```

Também testar:

- arquivos;
- relatórios;
- exportações;
- buscas;
- filtros;
- dashboards;
- cache;
- jobs.

## Frontend

Ocultar recurso não é autorização.

## DTO

Evitar campos sensíveis de escopo:

```text
TenantId
OwnerId
Role
Permission
```

quando devem ser definidos pelo servidor.

## Autenticação de sessão

A API Symfony deve usar estratégia de autenticação com access token de curta duração e refresh token protegido em cookie `HttpOnly`, `Secure` em produção e `SameSite` compatível com o fluxo da aplicação.

Regras de renovação, revogação e logout devem ser tratadas no backend.

## Segurança de uploads locais

Como o MVP utiliza filesystem local:

- salvar arquivos fora do diretório público;
- nunca confiar no nome original do arquivo como nome físico;
- gerar nome/identificador seguro no servidor;
- validar MIME type, extensão, tamanho e finalidade;
- impedir path traversal;
- registrar `tenant_id` nos metadados;
- autorizar download/visualização antes de servir arquivos privados;
- não expor caminhos absolutos do servidor na API;
- incluir o volume de uploads na política de backup.
