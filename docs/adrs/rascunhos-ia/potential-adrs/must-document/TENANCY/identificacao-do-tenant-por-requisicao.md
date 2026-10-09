# ADR Potencial: Como o tenant é identificado em cada requisição (usuário/token, não header)

**Módulo**: TENANCY
**Categoria**: Arquitetura / Segurança
**Prioridade**: Must Document (Score: 120; base 70 de infraestrutura de domínio + 50 das dimensões)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe. Relacionado a [schema-unico-com-tenant-id-e-escopo-global.md](./schema-unico-com-tenant-id-e-escopo-global.md) (o contexto só tem sentido com aquele modelo) e ao módulo AUTH (Sanctum, `AuthController`). Esta decisão teve duas fases; a primeira foi **substituída**.

---

## O Que Foi Identificado

**Fase atual (desde 2022-06/07)**: o middleware `tenant` (`IdentificarTenant`) lê `tenant_id` do usuário autenticado (`$request->user()`, que pode ser `User` na sessão do painel ou `Paciente` autenticado por token Sanctum na API), busca o `Tenant`, aborta com 401/403 se não houver, e o guarda em `TenantContext`. Na API, a rota é `['auth:sanctum', 'tenant']`. O único ponto em que o tenant vem de input do cliente é o login do paciente (`POST /api/v1/auth/token`), que recebe o `slug` da clínica e define o contexto manualmente em `AuthController`.

**Fase anterior (2021-03 a 2022-07)**: no modelo de schema por clínica, a identificação do tenant na API vinha do slug enviado pelo app no login e no header `X-Clinica` (Slack #arquitetura, 2021-02-18, Rafael: "o middleware ajusta o `search_path` antes do Sanctum"). Em `e75f608` (2021-03-04) o `DefinirSchemaTenant` passou a usar o header; na unificação, `827b1b7` criou `IdentificarTenantPorCabecalho` (busca `Tenant` por `X-Clinica`) e `827b1b7` o ligou antes de `auth:sanctum` na rota da API. Em 2022-07-12, `9356b12` ("tenant identificado pelo dono do token") **removeu** esse middleware e passou a derivar o tenant do dono do token, acrescentando um teste de isolamento (paciente do app não cancela agendamento de outra clínica). O commit não explica o motivo em texto; a inferência razoável é que um header controlado pelo cliente permitia inconsistência entre o `X-Clinica` e o dono do token. Marcar como **inferência**.

## Por Que Isto Merece um ADR

- **Impacto**: define a fronteira de confiança do isolamento. Todo request da API e do painel atravessa o middleware.
- **Trade-offs**: tenant derivado do principal autenticado é mais seguro que header, mas exige que o `tenant_id` do usuário/paciente seja confiável e que o login (única exceção) seja tratado com cuidado.
- **Conhecimento**: quem criar rota nova precisa saber que `tenant` deve estar no grupo e que, sem ele, o escopo global fica inativo (ver [escopo aberto](../../consider/TENANCY/escopo-aberto-sem-contexto-ativo-em-jobs-e-comandos.md)).
- **Temporal**: fase atual estável há ~4 anos; fase de header viveu ~16 meses.

## Evidências Encontradas

### Arquivos-chave
- [`app/Http/Middleware/IdentificarTenant.php`](../../../../../app/Http/Middleware/IdentificarTenant.php)
- [`app/Tenancy/TenantContext.php`](../../../../../app/Tenancy/TenantContext.php)
- [`app/Http/Controllers/Api/V1/AuthController.php`](../../../../../app/Http/Controllers/Api/V1/AuthController.php) - login recebe `clinica` (slug).
- [`routes/api.php`](../../../../../routes/api.php), [`app/Http/Kernel.php`](../../../../../app/Http/Kernel.php) - alias `tenant`.
- [`tests/Feature/Tenancy/IdentificarTenantTest.php`](../../../../../tests/Feature/Tenancy/IdentificarTenantTest.php) (`e5cb391`, 2024-09).

### Código
```php
$tenant = Tenant::find($usuario->tenant_id);
abort_if(! $tenant, 403);
$this->contexto->definir($tenant);
```

### Análise de Impacto
- Fase 1: `e75b0d0` (2022-06-08, `IdentificarTenant` para o painel); `e75f608` (2021-03-04, header em `DefinirSchemaTenant`).
- Transição: `827b1b7` (2022-06-28), `9356b12` (2022-07-12).
- Estável desde então; só testes adicionados em 2024.

### Alternativas observáveis
Header `X-Clinica` (2021-2022, descartado); subdomínio por clínica (não aparece em nenhuma fonte).

## Perguntas a Responder no ADR

- Qual foi exatamente o problema com o header? (inferência acima; confirmar com Beatriz Nogueira, autora de `9356b12`.)
- O que acontece no login quando o slug é inválido? (o código responde 422 "Credenciais inválidas" sem revelar a existência da clínica; confirmar que é intencional.)
- Papel `admin` global/suporte: há alguma forma de acessar várias clínicas? Não encontrada.

## ADRs Potenciais Relacionados
- [schema-unico-com-tenant-id-e-escopo-global.md](./schema-unico-com-tenant-id-e-escopo-global.md)
- [isolamento-por-schema-por-clinica-2019.md](./isolamento-por-schema-por-clinica-2019.md)
- AUTH: Sanctum com token por dispositivo (decisão de 2021-02, ver `contexto/slack/arquitetura.md`).

## Notas Adicionais

Redes de segurança: `personal_access_tokens` foi movida do schema da clínica (Slack, 2021-02-18) para o `public` na migração de 2022 (`08fab1a` copiou a tabela de migrations). Pacientes precisaram de novo login (aviso em `f60eb60`).
