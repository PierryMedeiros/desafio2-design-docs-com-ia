# ADR Potencial: Como a API identifica a clínica (header `X-Clinica` em 2021, dono do token desde 2022)

**Módulo**: API
**Categoria**: Segurança / Arquitetura (decisão superada)
**Prioridade**: Must Document (Score: 115)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe. Este ADR é fronteira entre API e TENANCY; o ADR de schema único (módulo TENANCY) deve referenciá-lo. Relacionado: [Sanctum](./sanctum-token-opaco-por-dispositivo-em-vez-de-jwt.md).

---

## O Que Foi Identificado

Houve duas estratégias sucessivas para descobrir a clínica de uma requisição da API.

**Fase 1 (2021-02 a 2022-07): header `X-Clinica` + schema por clínica.** Em 2021-02-18 (Slack #arquitetura) Beatriz Nogueira perguntou onde ficaria `personal_access_tokens`, já que o paciente loga antes de a clínica ser conhecida. Rafael Lima: no schema da clínica; o app manda o slug no login e no header `X-Clinica`, e o middleware ajusta o `search_path` antes do Sanctum. Foi implementado em `e75f608` (2021-03-04): o middleware `DefinirSchemaTenant` passou a usar `$request->user()` quando existe e, senão, `Tenant::where('slug', $request->header('X-Clinica'))`. Com a remoção do schema por clínica (`827b1b7`, 2022-06-28), o middleware virou `IdentificarTenantPorCabecalho`, ainda lendo o header.

**Fase 2 (2022-07-12 até hoje): clínica vem do dono do token.** O commit `9356b12` (Beatriz Nogueira) removeu `IdentificarTenantPorCabecalho` e o alias `tenant.cabecalho`; as rotas passaram para `['auth:sanctum', 'tenant']`, onde `IdentificarTenant` lê `tenant_id` do usuário autenticado e responde 403 se não houver tenant. O app não precisa mais enviar `X-Clinica` nas rotas protegidas (o slug só é usado no login). A ata de 2022-05-20 já previa "do usuário logado (ou do token, na API)" e dava a Beatriz a ação "ajustar API `/api/v1` e jobs para carregar o tenant" até 2022-06-10; a conclusão só ocorreu em 2022-07-12, quatro semanas depois, e nesse intervalo o header continuou sendo o mecanismo (confiança em dado enviado pelo cliente). O commit também adicionou teste de isolamento (`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`, +14 linhas) e `AuthTokenTest`.

O ADR deve registrar que o header foi uma decisão válida em 2021 (necessária para o schema por clínica), que a mudança de 2022 reduziu o risco de um cliente apontar o header para outra clínica, e o que fica em aberto: o endpoint de login ainda confia no slug enviado pelo cliente e o `TenantScope` não filtra quando não há contexto ativo (ver mapeamento, TENANCY).

## Por Que Isto Pode Merecer um ADR

- **Impacto**: é a barreira de isolamento entre clínicas na superfície mais exposta (app público). Falha aqui é vazamento de dado de saúde (LGPD).
- **Trade-offs**: header explícito (simples, facilita debug) contra identidade vinda do token (não manipulável pelo cliente).
- **Complexidade**: média, depende da interação entre `auth:sanctum`, `IdentificarTenant` e o escopo global.
- **Conhecimento do time**: obrigatório para quem criar rotas novas na API (toda rota protegida precisa do middleware `tenant`).
- **Implicações futuras**: usuário que pertença a mais de uma clínica não é suportado (um paciente = um `tenant_id`).
- **Contexto temporal**: fase 1 durou cerca de 16 meses; fase 2 estável há mais de 4 anos.

## Evidências Encontradas no Código

### Arquivos-chave
- [`routes/api.php`](../../../../../routes/api.php) - middleware `auth:sanctum` antes de `tenant`.
- [`app/Http/Middleware/IdentificarTenant.php`](../../../../../app/Http/Middleware/IdentificarTenant.php)
- [`app/Http/Controllers/Api/V1/AuthController.php`](../../../../../app/Http/Controllers/Api/V1/AuthController.php) - slug `clinica` no login define o contexto.
- Removido: `app/Http/Middleware/IdentificarTenantPorCabecalho.php` (visível em `git show 9356b12`).

### Evidência de Código
```php
// routes/api.php, antes de 9356b12
Route::middleware(['tenant.cabecalho', 'auth:sanctum'])->group(...)
// depois
Route::middleware(['auth:sanctum', 'tenant'])->group(...)
```
```php
// IdentificarTenantPorCabecalho (removido)
$tenant = Tenant::where('slug', $request->header('X-Clinica'))->firstOrFail();
```

### Análise de Impacto
- Header introduzido: 2021-03-04 (`e75f608`); renomeado em 2022-06-28 (`827b1b7`); removido em 2022-07-12 (`9356b12`).
- Afeta: API, AUTH, TENANCY, app mobile (deixou de precisar do header).
- Recursos de contexto: Slack 2021-02-18, ata 2022-05-20, postmortem 2022-04-12.

### Alternativas (observáveis)
- Token por schema da clínica e header (fase 1) superado pela identificação via token (fase 2).

## Questões a Responder no ADR (se criado)

- Por que o header foi mantido por quatro semanas após a unificação?
- O login deve continuar aceitando o slug da clínica enviado pelo cliente? E e-mail repetido em clínicas diferentes?
- Rotas novas da API devem ser protegidas por padrão via grupo?

## ADRs Potenciais Relacionados
- [Sanctum](./sanctum-token-opaco-por-dispositivo-em-vez-de-jwt.md)
- ADR de TENANCY (schema único com `tenant_id`), a ser identificado no módulo TENANCY.

## Notas Adicionais
- Pontuação: Autenticação como domínio crítico (base 70) + escopo 15 + custo 15 + conhecimento 15 = 115.
- O texto do Slack de 2021 sobre `X-Clinica` mapeado no `mapping.md` (item 9) é confirmado pelo git.
