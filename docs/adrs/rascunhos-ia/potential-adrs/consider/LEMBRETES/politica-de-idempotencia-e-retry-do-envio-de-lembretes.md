# ADR potencial: Política de idempotência e retry do envio de lembretes

**Módulo**: LEMBRETES
**Categoria**: Confiabilidade
**Prioridade**: Considerar (Pontuação: 80; escopo 15, custo de mudança 15, conhecimento 20, valor de incidentes reais)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR formal. Subdecisão da fila (`fila-redis-worker-dedicado-para-lembretes.md`); mantida separada por ter incidentes e valores próprios, mas pode ser consolidada.

---

## O que foi identificado

Evitar lembretes duplicados é uma preocupação desde 2020-02. A solução é em camadas: `withoutOverlapping` no scheduler; trava `Cache::add("lembretes:enfileirado:{id}", ..., 1h)` no enfileiramento (`8142e75`, 2020-02-27; proposta por Juliana "se o worker atrasar"); checagem no job de agendamento ativo e `lembrete_enviado_em` nulo; e gravação de `lembrete_enviado_em` após o envio.

Em 2024-03-12 (`83763e2`), após instabilidade do Twilio, Thiago Fonseca constatou job com `timeout=120` e `tries=5` enquanto o worker usava timeout padrão de 60 s: o worker matava o job, que voltava à fila, e às vezes o SMS já tinha saído (duplicado). Ajuste: job `timeout=30`, `tries=3`, `backoff=[30,120,300]`; worker `--tries=3 --timeout=60`; `retry_after` da conexão Redis em 90 (maior que o timeout do worker). Regra implícita: timeout do job < timeout do worker < `retry_after`.

## Por que isto merece um ADR

- Garantia é "pelo menos uma vez" com mitigação, não "exatamente uma vez": se o envio ocorre e o processo cai antes de gravar `lembrete_enviado_em`, ou o fallback WhatsApp aceito e falha depois, pode haver duplicidade. Isso deve estar explícito.
- A relação entre três parâmetros em arquivos distintos (job, `docker-compose.yml`, `config/queue.php`) é frágil e só está no Slack.
- Falhas definitivas após 3 tentativas: o que acontece com o lembrete (não há tratamento visível além de `failed_jobs`; a verificar).
- O motivo do 120 s/5 tentativas originais não está registrado.

## Evidências encontradas

### Evidência de código
```php
public $tries = 3;
public $timeout = 30;
public $backoff = [30, 120, 300];
```
(`app/Jobs/EnviarLembreteAgendamento.php`)

### Arquivos-chave
- [`app/Jobs/EnviarLembreteAgendamento.php`](../../../../../app/Jobs/EnviarLembreteAgendamento.php), [`app/Console/Commands/EnfileirarLembretes.php`](../../../../../app/Console/Commands/EnfileirarLembretes.php), [`docker-compose.yml`](../../../../../docker-compose.yml), [`config/queue.php`](../../../../../config/queue.php).

### Análise de impacto (git)
- `8142e75` (2020-02-27), `83763e2` (2024-03-12); `c068e49` (2025-04-15) trocou a checagem `status === AGENDADO` por `ativo()` (inclui confirmados).

## Questões a responder no ADR

- Qual garantia de entrega é aceita (duplicado versus perdido)?
- O que fazer com lembretes que esgotam as tentativas ou perdem a janela (como os ~300 de 2022-04-12)?
- Como manter coerentes timeout do job, do worker e `retry_after`?

## ADRs potenciais relacionados
- `fila-redis-worker-dedicado-para-lembretes.md`
- `interface-canallembrete-com-cadeia-de-fallback.md`
