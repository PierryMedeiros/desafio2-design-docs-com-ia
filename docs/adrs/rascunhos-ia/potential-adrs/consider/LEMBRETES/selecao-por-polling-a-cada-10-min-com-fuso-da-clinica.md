# ADR potencial: Seleção de lembretes por polling a cada 10 min, janela de 24 h e "agora" no fuso da clínica

**Módulo**: LEMBRETES
**Categoria**: Arquitetura / Decisão corrigida por incidente
**Prioridade**: Considerar (Pontuação: 76; escopo 15, custo 10, conhecimento 15, mais peso do incidente, por julgamento)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR formal. Relacionada ao módulo TENANCY (coluna `timezone` de `Tenant`) e à unificação de schema de 2022-06.

---

## O que foi identificado

O modelo de agendamento de lembretes é de polling: o scheduler roda `lembretes:enfileirar` a cada 10 min, e para cada clínica busca agendamentos `AGENDADO` ou `CONFIRMADO` sem `lembrete_enviado_em`, com início entre agora e agora + 24 h (`config('lembretes.antecedencia_horas')`). Não há agendamento por evento nem job atrasado por agendamento. A janela de 24 h vem do MVP (2019); o intervalo de 10 min também (`2187466`).

Em 2022-11-08, na reescrita do comando feita na unificação de schema (2022-06, `827b1b7`), a comparação passou a usar `now()` do Postgres em UTC contra horários gravados no horário de Brasília, e os lembretes saíram 3 h adiantados, alguns de madrugada. Hotfix `81c9949` (2022-11-08, 23:40): o "agora" é calculado em PHP no fuso de cada clínica (`Tenant::agora()` com `timezone`), com teste usando relógio fixo no fuso de São Paulo. O erro ficou quase 5 meses sem ser notado porque 27 h de antecedência passa despercebido.

## Por que isto merece um ADR

- Define uma regra de tempo que atravessa dados (horários locais gravados sem fuso), banco (UTC) e tenants (fuso por clínica); a causa do incidente foi falta de regra explícita.
- Consequência: a granularidade real do lembrete é de ~10 min e a antecedência é "até 24 h", não exata.
- Em 2025-04-15 (`c068e49`) a seleção passou a incluir confirmados, o que mostra que a regra de elegibilidade evolui (detalhe de negócio; não é ADR por si).

## Evidências encontradas

### Arquivos-chave
- [`app/Console/Commands/EnfileirarLembretes.php`](../../../../../app/Console/Commands/EnfileirarLembretes.php), [`app/Models/Tenant.php`](../../../../../app/Models/Tenant.php), [`config/lembretes.php`](../../../../../config/lembretes.php), [`tests/Feature/Lembretes/EnfileirarLembretesTest.php`](../../../../../tests/Feature/Lembretes/EnfileirarLembretesTest.php).

### Evidência de código
```php
$agora = $tenant->agora();   // CarbonImmutable::now($this->timezone)
$limite = $agora->addHours(config('lembretes.antecedencia_horas'));
```

### Análise de impacto (git)
- `4115d91` (2019-07-05, não enviar para cancelado), `81c9949` (2022-11-08), `c068e49` (2025-04-15).
- Fonte do incidente: Slack #geral 2022-11-08 a 2022-11-09.

## Questões a responder no ADR

- Convenção oficial: horários de agendamento são locais da clínica? `config/app.php` usa `America/Sao_Paulo` global.
- Por que polling e não agendamento por evento?
- Como lidar com o restante do sistema (relatórios, `now()` em SQL) para evitar a mesma classe de erro?

## ADRs potenciais relacionados
- `fila-redis-worker-dedicado-para-lembretes.md`

## Notas adicionais
Itens descartados por filtros: log de canal em dev (`8e6ba6a`, trivial), link da teleconsulta na mensagem (`b753a26`, regra de negócio), `Telefone::e164` (`13eb367`, detalhe), antecedência de 24 h como número isolado (configuração). A pontuação é aproximada e depende de julgamento do time.
