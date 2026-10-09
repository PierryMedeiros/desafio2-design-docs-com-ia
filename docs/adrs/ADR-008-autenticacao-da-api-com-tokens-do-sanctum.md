# ADR-008: Autenticação da API com tokens do Sanctum

- **Status:** Accepted
- **Data:** 2021-02-17
- **Decisores:** Rafael Lima (CTO), Beatriz Nogueira, Juliana Prado
- **Relações:**
  - depends on [ADR-007: API REST versionada na URL, dentro do monólito](ADR-007-api-rest-versionada-na-url-no-monolito.md)
  - relates to [ADR-013: Schema único com tenant_id e escopo global](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md) (com a unificação, a clínica do token passou a vir do paciente dono do token, `9356b12`)

## Contexto e problema

O app do paciente precisava autenticar o paciente na nova API ([ADR-007](ADR-007-api-rest-versionada-na-url-no-monolito.md)). Uma preocupação levantada na discussão foi a revogação: "paciente perde o celular, clínica bloqueia o paciente, como derruba o token?" (Juliana, `contexto/slack/arquitetura.md`, 2021-02-17).

## Opções consideradas

1. **JWT com `tymon/jwt-auth`**, proposta da Beatriz, que usava no emprego anterior. Para revogar, ela sugeriu uma blacklist no cache ou um token curto com refresh.
2. **Laravel Sanctum**, com token opaco guardado no banco, proposta do Rafael.

## Decisão

Opção 2: Sanctum, com um token por dispositivo. Rafael: "fechado: REST, /api/v1, Sanctum com um token por dispositivo".

Motivos registrados:

- **JWT stateless é chato de revogar** (Juliana). A blacklist ou o refresh token seriam "reinventar sessão em cima de jwt" (Rafael).
- **Revogar no Sanctum é apagar a linha** do token no banco (Rafael).
- **Sanctum é oficial do Laravel:** "menos uma dependência de terceiro" (Beatriz).

Implementação:

- `4381c6b`: instala o Sanctum e cria a tabela `personal_access_tokens`.
- `e75f608`: `POST /api/v1/auth/token` com e-mail, senha e clínica, e a senha do paciente em `pacientes.senha`.
- `7e10085`: a recepção define a senha do app na ficha do paciente.

Com o schema por clínica, os tokens ficavam no schema da clínica, e o app mandava o slug no login e no header `X-Clinica` (Slack, 2021-02-18). Depois da unificação ([ADR-013](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)), a clínica passou a vir do paciente dono do token (`9356b12`, 2022-07-12).

## Consequências

### Positivas

- Os tokens podem ser revogados individualmente, por dispositivo, apagando o registro.
- Sem dependência de terceiros para autenticação. O Sanctum acompanhou os upgrades do Laravel.
- O middleware `auth:sanctum` se integra ao mesmo modelo de usuário autenticado usado pelo `IdentificarTenant`.

### Negativas

- Toda requisição autenticada consulta `personal_access_tokens` no banco.
- O token não expira por padrão: `config/sanctum.php` tem `expiration => null`. A coluna `expires_at` entrou em `40d1dc9` (2023-07-11), mas o login não define validade.
- O código não tem um endpoint de logout ou revogação pelo app. **Needs Input:** como a revogação é feita na prática ("clínica bloqueia o paciente", "paciente perde o celular"), que foi o argumento central da escolha?

## Evidências

- Commits: `4381c6b`, `e75f608`, `7e10085`, `9356b12`, `40d1dc9`.
- Arquivos: `config/sanctum.php`, `app/Http/Controllers/Api/V1/AuthController.php`, `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`, `routes/api.php`.
- Rastros: `contexto/slack/arquitetura.md` (2021-02-17 e 2021-02-18).
