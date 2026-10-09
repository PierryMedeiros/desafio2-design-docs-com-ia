# ADR Potencial: Escopo de tenant "aberto por padrão" quando não há contexto (jobs e comandos)

**Módulo**: TENANCY
**Categoria**: Segurança / Arquitetura
**Prioridade**: Consider (Score: 85; base 70 + 15). Pode subir para Must Document se a equipe decidir tratá-lo como risco aceito.
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal. Faz parte do mesmo conjunto de [schema-unico-com-tenant-id-e-escopo-global.md](../../must-document/TENANCY/schema-unico-com-tenant-id-e-escopo-global.md) e [identificacao-do-tenant-por-requisicao.md](../../must-document/TENANCY/identificacao-do-tenant-por-requisicao.md). Sugestão de consolidação: pode virar uma seção de "Consequências" do ADR principal em vez de um ADR separado.

---

## O Que Foi Identificado

`TenantScope::apply` só filtra quando `TenantContext::ativo()`. Sem contexto (comandos do scheduler, jobs de fila, seeders, tinker), a consulta roda **sem filtro**, retornando dados de todas as clínicas; e `BelongsToTenant` só preenche `tenant_id` no `creating` se houver contexto. Esse comportamento é "fail-open". Não há registro de motivo; na linguagem de `contexto/LEIA-ME.md`, é candidato a "sempre foi assim".

O que existe de fato:
- O comando `lembretes:enfileirar` (global, sem contexto) filtra à mão com `where('tenant_id', ...)` por clínica e calcula `agora` no fuso do tenant (`81c9949`).
- O job `EnviarLembreteAgendamento` recebe só o `agendamentoId` e faz `Agendamento::find` sem escopo. Na ata de 2022-05-20 a intenção era que "os jobs carreguem o `tenant_id` no payload"; o job do modelo antigo tinha `tenantId` e trocava o schema; `827b1b7` (2022-06-28) removeu o `tenantId` do payload. Ou seja, a intenção da ata não foi mantida (o ID do agendamento é global, então o job funciona, mas sem defesa em profundidade). Inferência a validar com o time.
- `CriptografarDadosPacientes` usa `DB::table` direto (uso pontual, pré-unificação). Testes também usam `DB::table`/`withoutGlobalScopes` para inspecionar dados brutos (aceitável).
- `tenant_id` é `nullable` nas tabelas migradas em 2022 (`08fab1a`): linhas criadas sem contexto ficariam órfãs e, com escopo ativo, invisíveis.

## Por Que Isto Merece um ADR

- **Impacto**: qualquer novo comando ou job que consulte models de clínica sem contexto lê todas as clínicas; uma consulta que crie registros sem contexto grava `tenant_id` nulo.
- **Trade-offs**: simplicidade (comandos e seeders funcionam sem cerimônia) versus risco de vazamento. A ata de 2022-05-20 aceita explicitamente o risco de escapar do escopo e promete mitigação por teste e revisão.
- **Conhecimento**: devs que escrevem jobs precisam saber esta regra.
- **Futuro**: alternativa de "falhar fechado" (exceção se não houver contexto, com `withoutTenant()` explícito) ou RLS como defesa em profundidade.

## Evidências Encontradas

- [`app/Tenancy/TenantScope.php`](../../../../../app/Tenancy/TenantScope.php) linhas 11-16.
- [`app/Tenancy/BelongsToTenant.php`](../../../../../app/Tenancy/BelongsToTenant.php) - preenchimento condicionado a `ativo()`.
- [`app/Console/Commands/EnfileirarLembretes.php`](../../../../../app/Console/Commands/EnfileirarLembretes.php) - filtro manual por tenant.
- [`app/Jobs/EnviarLembreteAgendamento.php`](../../../../../app/Jobs/EnviarLembreteAgendamento.php) - `find` por ID.
- `tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php` - cobre rotas do painel e da API; não encontrei teste de job nem de comando isolando clínicas (a ata de 2022 pedia "nenhuma rota, consulta ou job").

### Análise de Impacto
- Introduzido: `396964c` (2022-06-07); payload do job alterado em `827b1b7` (2022-06-28).
- Sem alterações no escopo desde então.

## Perguntas a Responder

- A decisão de ficar aberto foi deliberada ou omissão? Rafael (autor) saiu em 06/2024.
- Há outros jobs/comandos que dependem do escopo (ex.: `RelatorioFaltas`, o aviso da lista de espera de `3f24942`)? Levantar.
- Vale tornar `tenant_id` NOT NULL e falhar fechado?

## ADRs Potenciais Relacionados
- [schema-unico-com-tenant-id-e-escopo-global.md](../../must-document/TENANCY/schema-unico-com-tenant-id-e-escopo-global.md)
- [identificacao-do-tenant-por-requisicao.md](../../must-document/TENANCY/identificacao-do-tenant-por-requisicao.md)
- LEMBRETES: fila `notificacoes` e job idempotente.

## Notas Adicionais

Incerteza: o relato de que os jobs deveriam carregar `tenant_id` vem só da ata; o código não confirma. Mantido como "consider" porque a evidência de decisão consciente é fraca.
