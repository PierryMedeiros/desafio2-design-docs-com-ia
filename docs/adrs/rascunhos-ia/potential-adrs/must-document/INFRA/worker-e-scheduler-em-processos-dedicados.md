# ADR Potencial: Worker de fila e scheduler como processos dedicados (substituindo cron e supervisor da VPS)

**Módulo**: INFRA
**Categoria**: Arquitetura
**Prioridade**: Must Document (Score: 115)
**Data de Identificação**: 2026-10-09

---

## Existing ADR Context

⚠️ **SOBREPOSIÇÃO PARCIAL**

- `LEMBRETES/fila-redis-worker-dedicado-para-lembretes.md` trata da fila e do worker pelo lado dos lembretes. Aqui o foco é o modelo de processos (cron/supervisor da VPS -> scheduler e worker em contêiner) e a regra de timeouts. Considerar consolidar a parte de worker com aquele ADR e manter aqui o scheduler e a topologia de processos.

---

## O que foi identificado

O trabalho em segundo plano do Horalis passou por três modelos:
1. **2019-06 (`2187466`, `48fa4b5`)**: lembretes por e-mail dentro de um comando no cron da VPS (`schedule:run` a cada minuto, `scripts/crontab`), envio síncrono.
2. **2020-02-12/17 (`e4cf1d5`, `2fb26b9`, `d79772d`)**: após lembretes duplicados em 2020-02-11, o comando virou `lembretes:enfileirar` e o envio foi para um worker de fila Redis (`queue:work redis --queue=notificacoes,default`), gerenciado na VPS por supervisor (`scripts/supervisor/horalis-worker.conf`, `autorestart=true`, `stopwaitsecs=130`). O Slack cita "container `worker`, com supervisor reiniciando se cair".
3. **2021-06 em diante**: ao migrar para a AWS, o script de deploy, o crontab e o supervisor foram removidos (`cad4e26`, 2021-06-08) e o scheduler virou contêiner próprio rodando `php artisan schedule:work` (`d93c79b`, 2021-06-15). O worker é contêiner próprio (`docker-compose.yml`).

O comando é agendado de 10 em 10 minutos com `withoutOverlapping` (`app/Console/Kernel.php`, linha 15). A coordenação de limites é uma decisão consolidada em 2024-03-12 (`83763e2`), após instabilidade do Twilio: job `timeout=30`, `tries=3`, `backoff=[30,120,300]`; worker `--tries=3 --timeout=60` (antes `--tries=5`); `retry_after=90` na conexão. A regra: timeout do job < timeout do worker < `retry_after`. Antes da correção, job com 120 s e worker com 60 s fazia o worker matar o job e reentregá-lo, e o SMS às vezes já tinha saído (duplicidade).

Pontos em aberto: apenas um contêiner de worker e um scheduler (o postmortem de 2022 cita "um contêiner de worker"); se o scheduler escalar para duas instâncias, `withoutOverlapping` depende de cache compartilhado (Redis, ok), mas não há registro de decisão sobre isso. Também não há procedimento automático para pausar o worker durante deploy (ação do postmortem 2022, responsável Marcos Teixeira, resultado desconhecido).

## Por que isso pode merecer um ADR

- **Impacto**: LEMBRETES e qualquer trabalho assíncrono futuro.
- **Trade-offs**: simplicidade (processo único) vs disponibilidade; duplicidade vs perda de mensagem.
- **Complexidade**: relação entre timeout de job, timeout do worker e `retry_after` é fonte de bugs reais (2020-02 e 2024-03).
- **Conhecimento do time**: quem cria jobs novos precisa saber a regra.
- **Temporal**: modelo atual estável há 5 anos.

## Evidências encontradas no código

### Arquivos principais
- [`docker-compose.yml`](../../../../../docker-compose.yml) - serviços `worker` e `scheduler`
- [`app/Console/Kernel.php`](../../../../../app/Console/Kernel.php) linha 15
- [`config/queue.php`](../../../../../config/queue.php) - `retry_after => 90`
- [`app/Jobs/EnviarLembreteAgendamento.php`](../../../../../app/Jobs/EnviarLembreteAgendamento.php)

### Análise de impacto
- Introduzido: 2019-06 (cron) -> 2020-02 (worker) -> 2021-06 (scheduler em contêiner) -> 2024-03 (limites)
- Temas: duplicidade de lembretes, timeouts, backoff

### Alternativas (Slack 2020-02-11)
- `withoutOverlapping` isolado: paliativo (resolve a duplicação, não o atraso).
- Fila em `database` e SQS (ver `redis-fila-cache-e-sessao.md`).

## Questões a responder no ADR

- Há mais de um worker em produção? O scheduler é único?
- Como o worker é pausado/reiniciado em deploys (`queue:restart` existia no script antigo)?
- Qual a política de filas (`notificacoes` vs `default`) e de jobs falhos (`failed_jobs`)?

## ADRs potenciais relacionados
- `redis-fila-cache-e-sessao.md`
- `LEMBRETES`: job, idempotência e canais
- `conteineres-docker-compose-nginx-php-fpm.md`
