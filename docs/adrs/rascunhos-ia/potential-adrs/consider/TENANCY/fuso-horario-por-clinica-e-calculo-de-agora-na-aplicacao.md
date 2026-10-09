# ADR Potencial: Fuso horário por clínica e "agora" calculado na aplicação (não no banco)

**Módulo**: TENANCY
**Categoria**: Arquitetura / Confiabilidade
**Prioridade**: Consider (Score: 75)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal. Relacionado ao módulo LEMBRETES e AGENDA. A decisão de modelagem é de TENANCY porque o fuso é atributo da clínica (`tenants.timezone`).

---

## O Que Foi Identificado

Cada clínica tem um fuso (`tenants.timezone`, default `America/Sao_Paulo`, criado em `3b304c1`/2019-05-06). A aplicação roda em `America/Sao_Paulo` (`config/app.php`, `9657955`, 2019-04-03), e os horários dos agendamentos são gravados como horário local da clínica (sem fuso). O banco (Postgres) roda em UTC. Logo, qualquer comparação com `now()` do banco está errada em 3 horas. A convenção atual é: "agora" é calculado no PHP, no fuso do tenant (`Tenant::agora()`, `e3b2252`, 2025-01), e comparado com os horários gravados.

A convenção nasceu de incidentes:
- 2022-03-15 (`a00525b`): API de horários livres passou a descartar horários passados no fuso da clínica.
- 2022-06-28 (`827b1b7`): na reescrita do comando de lembretes para schema único, a consulta passou a usar `whereRaw("inicio between now() and now() + interval '24 hours'")`, introduzindo o bug.
- 2022-11-08 (`81c9949`, Juliana, 23h40): hotfix após reclamação de Helena: lembretes saindo 3 h adiantados (paciente com consulta às 9h recebeu o lembrete às 6h). Correção: loop por tenant, `agora` calculado em PHP no fuso da clínica, teste com relógio fixo. O bug ficou ~4 meses em produção; só foi percebido quando lembretes passaram a chegar de madrugada.

Os horários são tratados como "wall clock" local; isto limita clínicas fora do fuso de SP (hoje o campo existe, mas não há evidência de clínicas em outros fusos).

## Por Que Isto Merece um ADR

- **Impacto**: agenda, API de horários, lembretes e relatórios (`RelatorioFaltas`, `AgendaController`, `HorarioController` usam `$tenant->agora()`).
- **Trade-offs**: simplicidade (sem conversão UTC) versus risco de bugs de fuso e de horário de verão (inexistente no Brasil desde 2019, mas não universal).
- **Conhecimento**: todo dev que escreve consulta temporal deve saber que `now()` do banco não serve.
- **Custo de mudar**: guardar em UTC exigiria migrar todos os horários gravados e todos os clientes (painel, app v1 que não pode ser forçado a atualizar): meses.

## Evidências Encontradas

- [`app/Models/Tenant.php`](../../../../../app/Models/Tenant.php) - `agora()`.
- [`app/Console/Commands/EnfileirarLembretes.php`](../../../../../app/Console/Commands/EnfileirarLembretes.php).
- [`config/app.php`](../../../../../config/app.php) - `'timezone' => 'America/Sao_Paulo'`.
- Slack #geral, 2022-11-08 21:12 a 2022-11-09 09:25.
- [`tests/Feature/Lembretes/EnfileirarLembretesTest.php`](../../../../../tests/Feature/Lembretes/EnfileirarLembretesTest.php).

### Análise de Impacto
- Coluna `timezone`: 2019-05-06 (`3b304c1`).
- Correções: `a00525b` (2022-03), `81c9949` (2022-11).
- Consolidação: `e3b2252` (2025-01).

## Perguntas a Responder

- Por que foi escolhido gravar horário local e não UTC (2019)? Não há registro.
- Existe clínica fora de `America/Sao_Paulo`? E o Postgres está mesmo em UTC (afirmação de Juliana no Slack)? Confirmar no RDS.

## ADRs Potenciais Relacionados
- [schema-unico-com-tenant-id-e-escopo-global.md](../../must-document/TENANCY/schema-unico-com-tenant-id-e-escopo-global.md) (o bug veio da reescrita da unificação)

## Notas Adicionais

Item de baixa certeza: pode ser melhor tratado como parte de AGENDA/LEMBRETES. Se a equipe preferir, vira seção de outro ADR.
