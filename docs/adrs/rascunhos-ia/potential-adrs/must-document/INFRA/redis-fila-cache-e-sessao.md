# ADR Potencial: Redis como fila, cache e sessão (e descarte de fila em banco e SQS)

**Módulo**: INFRA
**Categoria**: Tecnologia
**Prioridade**: Must Document (Score: 150)
**Data de Identificação**: 2026-10-09

---

## Existing ADR Context

ℹ️ **DECISÕES RELACIONADAS**

- `LEMBRETES/fila-redis-worker-dedicado-para-lembretes.md` (decisão de aplicação da fila, 145) e `AUTH/sessao-armazenada-no-redis.md` (sessão, 95) cobrem usos específicos. Este potencial ADR cobre o serviço Redis como infraestrutura compartilhada (três usos, ponto único de falha). Ao gerar os ADRs formais, referenciá-los entre si ou consolidar a parte de fila.

---

## O que foi identificado

O Redis entrou em 2020-02-12 (`e4cf1d5`, "redis e worker no docker-compose") para tirar o envio de lembretes de dentro do comando agendado. A decisão foi tomada no Slack #arquitetura em 2020-02-12, depois que lembretes duplicados em 2020-02-11 mostraram que `lembretes:enviar` (síncrono) passava de 14 min e se sobrepunha ao cron de 10 min. Alternativas discutidas: driver `database` (descartado por Rafael: polling e lock na tabela `jobs` pesariam no Postgres, que "já fica apertado na VPS", e havia dúvida de em qual schema a tabela ficaria) e SQS (descartado: "a gente nem tá na AWS... se um dia a gente for pra lá a gente revê"). Escolhido: Redis + worker dedicado. O job `EnviarLembreteAgendamento` foi criado em `2fb26b9` (2020-02-13) e o comando renomeado em `d79772d` (2020-02-17).

O uso foi ampliado em 2021-08: sessões e cache migraram de `file` para Redis em `4e96d46` (2021-08-17), após a segunda instância expor deslogamentos (ver `aplicacao-stateless-multi-instancia-atras-de-balanceador.md`). A trava anti-duplicidade dos lembretes (`Cache::add` por agendamento) depende do cache em Redis. Em produção é ElastiCache (Slack 2021-06-07 e postmortem 2022). O cliente PHP é `predis` (adicionado em `2fb26b9`). Versões no compose: 7 (`3962b4f`, 2023-04-11), fixada em `redis:7.2.16-alpine` (`989d7cc`).

Com a decisão de 2020 o Redis nasceu como fila, mas hoje é dependência crítica de três funções (fila, cache/lock, sessão): uma queda derruba login, lembretes e idempotência ao mesmo tempo. O ponto de 2020 "se um dia formos para a AWS, revê" foi cumprido de fato em 2021-06 sem reavaliar SQS (nenhum registro disso).

## Por que isso pode merecer um ADR

- **Impacto**: LEMBRETES, AUTH (sessão), PAINEL, todo código com `Cache::`.
- **Trade-offs**: simplicidade e velocidade vs ponto único de falha; fila em Redis perde jobs se o Redis perder dados sem persistência configurada (no postmortem de 2022 "os jobs ficaram no Redis e puderam ser reprocessados").
- **Complexidade**: coordenação de timeouts: job `timeout=30`, worker `--timeout=60`, `retry_after=90` (`83763e2`, 2024-03-12, após job repetido durante instabilidade do Twilio).
- **Conhecimento do time**: quem mexe em jobs ou sessão precisa saber disso.
- **Temporal**: estável há mais de 6 anos.

## Evidências encontradas no código

### Arquivos principais
- [`config/queue.php`](../../../../../config/queue.php) - `'default' => env('QUEUE_CONNECTION','redis')`, `retry_after => 90` (linhas 41, 49, 69)
- [`.env.example`](../../../../../.env.example) - `CACHE_DRIVER=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, `REDIS_CLIENT=predis`
- [`config/session.php`](../../../../../config/session.php) - driver padrão `redis` (linha 21)
- [`docker-compose.yml`](../../../../../docker-compose.yml) - serviço `redis`, worker com `--queue=notificacoes,default --tries=3 --timeout=60`
- [`phpunit.xml`](../../../../../phpunit.xml) - testes usam `QUEUE_CONNECTION=sync` e `CACHE_DRIVER=array`

### Evidência de código
```
# docker-compose.yml
command: php artisan queue:work redis --queue=notificacoes,default --tries=3 --timeout=60
```

### Análise de impacto
- Introduzido: 2020-02-12 (`e4cf1d5`), job em `2fb26b9`
- Ampliado: sessão e cache em `4e96d46` (2021-08-17)
- Ajuste de tries/timeout/backoff: `83763e2` (2024-03-12)
- Temas: duplicidade de lembretes, deslogamento em múltiplas instâncias, jobs repetidos por timeout

### Alternativas (registradas no Slack, 2020-02-12)
- Fila em `database`: descartada (carga no Postgres, dúvida de schema).
- SQS: descartada (não havia AWS).
- `withoutOverlapping` apenas: paliativo, não resolve atraso; entrou junto com a fila.

## Questões a responder no ADR

- Redis tem persistência (AOF/RDB) ou multi-AZ no ElastiCache? (não consta)
- Vale separar instâncias/DBs de Redis para fila, cache e sessão?
- Com a AWS, SQS foi reavaliado? Por que não?
- Por que a política de retry (3 tentativas, backoff 30/120/300 s) é essa?

## ADRs potenciais relacionados
- `LEMBRETES`: job assíncrono e fallback de canais
- `AUTH`: sessão no Redis
- `worker-e-scheduler-em-processos-dedicados.md`
- `aplicacao-stateless-multi-instancia-atras-de-balanceador.md`

## Notas adicionais
- O mapping.md já destaca o Redis como dependência crítica.
- Parte da decisão (jobs) nasceu em LEMBRETES; este ADR trata do serviço Redis e dos três usos.
