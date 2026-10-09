# ADR Potencial: Horários gravados no fuso local, sem fuso, no banco (e fuso por clínica)

**Módulo**: DATA (atravessa AGENDA, API, LEMBRETES e PAINEL)
**Categoria**: Arquitetura / Dados
**Prioridade**: Considerar (Pontuação: 75 = 0 base + 25 escopo + 25 custo de mudança + 25 conhecimento). Ficou no limite: sem os dois incidentes em produção abaixo seria rebaixada.
**Data da identificação**: 2026-10-09

---

## Contexto de ADRs existentes

**POTENCIAL PARCIALMENTE DUPLICADO** (detectado em 2026-10-09, após a identificação):
- `../TENANCY/fuso-horario-por-clinica-e-calculo-de-agora-na-aplicacao.md` (Score 75) e `../LEMBRETES/selecao-por-polling-a-cada-10-min-com-fuso-da-clinica.md` (Score 76) tratam do mesmo incidente de 2022-11. Este arquivo se distingue por abordar a causa de dados (colunas sem fuso, banco em UTC). Consolidar em um único ADR.

---

## O que foi identificado

É uma decisão implícita, nunca discutida nas fontes de contexto. A aplicação foi configurada no fuso `America/Sao_Paulo` logo no começo (`9657955`, 2019-04-03: `'timezone' => 'UTC'` trocado para `America/Sao_Paulo`). Os horários de agendamento (`inicio`, `fim`) são colunas `dateTime` (no Postgres, `timestamp without time zone`) gravadas no horário local, sem informação de fuso (`2019_04_18_171000_create_agendamentos_table.php`, `fb963ec`, 2019-04-18). Já o banco (RDS) roda em UTC. Em 2019-05 a tabela `tenants` ganhou `timezone` com padrão `America/Sao_Paulo` (`3b304c1`); o método `Tenant::agora()`, que dá o "agora" no fuso da clínica, só apareceu em 2025-01 (`e3b2252`); antes disso o hotfix de 2022 usava `now($tenant->timezone)`.

Duas falhas mostram o custo dessa combinação: (1) 2022-03-15, a API de horários livres ignorava o fuso da clínica (`a00525b`); (2) 2022-11-08, o `lembretes:enfileirar` reescrito na unificação comparava `inicio` com o `now()` do Postgres (UTC) e os lembretes saíam 3h adiantados, só notado quando pacientes receberam lembretes de madrugada; o hotfix (`81c9949`, 2022-11-08 23:40) passou a calcular o "agora" em PHP no fuso da clínica e a testar com relógio fixo (Slack #geral, 2022-11-08). Nesse caso houve também reenfileiramento manual dos lembretes já na fila.

## Por que isso pode merecer um ADR

- **Impacto**: todo cálculo de disponibilidade, conflito, lembrete e relatório que compare horário. O contrato da API v1 (app instalado nos celulares) devolve horários nesse formato.
- **Trade-offs**: simples de ler e escrever (sem conversão) versus ambiguidade (sem fuso no dado), horário de verão e clínicas fora do fuso padrão dependem de lógica espalhada.
- **Complexidade**: armadilha recorrente: qualquer SQL com `now()` ou `interval` no Postgres usa UTC.
- **Conhecimento do time**: obrigatório para quem mexer em consulta temporal; a regra "calcule o agora em PHP com `$tenant->agora()`" só existe no código e no Slack.
- **Implicações futuras**: migrar para `timestamptz` exigiria converter dados de ~900 clínicas e alinhar o app.
- **Contexto temporal**: estável há 7 anos; duas correções em 2022.

## Evidências encontradas no código

### Arquivos-chave
- [`config/app.php`](../../../../config/app.php) linha 77: `'timezone' => 'America/Sao_Paulo'`.
- [`app/Models/Tenant.php`](../../../../app/Models/Tenant.php) (`agora()`, linhas 25-28).
- [`app/Console/Commands/EnfileirarLembretes.php`](../../../../app/Console/Commands/EnfileirarLembretes.php) linhas 21-31: "agora" por clínica.
- [`database/migrations/2019_04_18_171000_create_agendamentos_table.php`](../../../../database/migrations/2019_04_18_171000_create_agendamentos_table.php) linhas 21-22: `dateTime`.
- [`tests/Feature/Lembretes/EnfileirarLembretesTest.php`](../../../../tests/Feature/Lembretes/EnfileirarLembretesTest.php): teste com relógio fixo.

### Evidência de código
```php
// app/Console/Commands/EnfileirarLembretes.php
$agora = $tenant->agora();
$limite = $agora->addHours(config('lembretes.antecedencia_horas'));
```

### Análise de impacto (git)
- Introduzido: 2019-04-03 (`9657955`), colunas em 2019-04-18 (`fb963ec`) e fuso por clínica em 2019-05-06 (`3b304c1`).
- Correções: `a00525b` (2022-03-15), `81c9949` (2022-11-08).
- Temas: "fuso", "adiantados", "relógio fixo".

### Alternativas
Nenhuma discutida. Implícitas: `timestamptz` com UTC no banco.

## Questões a responder no ADR (se criado)

- Foi uma escolha consciente gravar horário local? (nenhuma fonte diz; provavelmente "sempre foi assim".)
- Como tratar horário de verão e clínicas em outro fuso (o campo existe, mas só há `America/Sao_Paulo` por padrão)?
- Devolver horários na API com ou sem offset?

## ADRs potenciais relacionados
- [Schema único com tenant_id](../../must-document/DATA/schema-unico-com-tenant-id-substitui-schema-por-clinica.md) (o bug de 2022-11 nasceu na reescrita do comando durante a unificação).
- [PostgreSQL](../../must-document/DATA/postgresql-como-banco-relacional.md)

## Notas adicionais

