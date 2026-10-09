# ADR potencial: Envio de lembretes assíncrono com fila Redis e worker dedicado

**Módulo**: LEMBRETES
**Categoria**: Arquitetura / Infraestrutura
**Prioridade**: Documentar obrigatoriamente (Pontuação: 145; Etapa 0, Categoria 1, serviço de infraestrutura)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR formal existe ainda. Relacionada a decisões de outros módulos (a validar na análise deles): Redis reaproveitado depois para sessões (`4e96d46`, 2021-08) e cache/trava; migração para AWS ElastiCache (2021-06, apenas Slack).

---

## O que foi identificado

Os lembretes nasceram síncronos: o comando `lembretes:enviar` rodava no cron a cada 10 minutos, enviava um e-mail por SMTP dentro do próprio comando e gravava `lembrete_enviado_em` (`2187466`, 2019-06-04; "por enquanto síncrono mesmo, são 3 clínicas", Rafael Lima, Slack #arquitetura 2019-06-03). Em 2020-02-11 a Clínica Movimento reclamou de lembretes duplicados ou triplicados: com mais clínicas a execução levou 14 min, a execução seguinte do cron começou antes da anterior terminar e as duas pegaram os mesmos agendamentos.

Em 2020-02-12 o time decidiu tirar o envio de dentro do comando: `lembretes:enviar` virou `lembretes:enfileirar` (`d79772d`), que apenas seleciona e despacha um job `EnviarLembreteAgendamento` por lembrete (`2fb26b9`) na fila `notificacoes`, consumido por um contêiner `worker` com Redis (`e4cf1d5`, que também trouxe o supervisor). A fila nomeada foi separada da `default` "porque vai ter outras coisas lá". Entrou em produção em 2020-02-17 (segunda-feira; deploy de sexta recusado). O comando passou de 14 min para ~3 s e não houve mais reclamação de duplicados no dia seguinte.

## Por que isto merece um ADR

- **Impacto**: introduz Redis, um processo worker e um scheduler como peças obrigatórias da operação; hoje `docker-compose.yml` tem `worker` (`queue:work redis --queue=notificacoes,default --tries=3 --timeout=60`) e `scheduler` (`schedule:work`).
- **Alternativas explícitas (Slack 2020-02-12)**: driver `database` (descartado: polling e lock na tabela `jobs` pesariam no Postgres já apertado na VPS, e havia dúvida sobre em qual schema ficaria a tabela); `sqs` (descartado: "a gente nem tá na AWS... se um dia a gente for pra lá a gente revê"); `withoutOverlapping` isolado (paliativo: evita duplicação mas atrasa lembretes). `withoutOverlapping` foi mantido junto.
- **Decisão condicionada a um contexto que mudou**: a produção migrou para AWS em 2021-06, e a premissa "não estamos na AWS" que descartou SQS deixou de valer; a revisão prometida não aparece registrada.
- **Consequência observada**: no incidente de 2022-04-12 (`docs/postmortems/2022-04-12-deploy-travado.md`) os jobs ficaram no Redis e puderam ser reprocessados; ~1.900 lembretes atrasaram e ~300 não saíram.
- **Estabilidade**: em uso há mais de 6 anos.

## Evidências encontradas

### Arquivos-chave
- [`app/Console/Commands/EnfileirarLembretes.php`](../../../../../app/Console/Commands/EnfileirarLembretes.php) - seleciona e despacha.
- [`app/Jobs/EnviarLembreteAgendamento.php`](../../../../../app/Jobs/EnviarLembreteAgendamento.php) - `onQueue('notificacoes')`.
- [`app/Console/Kernel.php`](../../../../../app/Console/Kernel.php) - `everyTenMinutes()->withoutOverlapping()`.
- [`docker-compose.yml`](../../../../../docker-compose.yml) - serviços `worker` e `scheduler`; [`config/queue.php`](../../../../../config/queue.php) - `retry_after` 90.

### Evidência de código
```php
$schedule->command('lembretes:enfileirar')->everyTenMinutes()->withoutOverlapping();
```

### Análise de impacto (git)
- Introduzido: 2020-02-12 a 2020-02-17 (`e4cf1d5`, `2fb26b9`, `d79772d`, `8142e75`).
- Estado anterior: envio síncrono por e-mail via cron (`2187466`, 2019-06-04).
- Tema das mudanças posteriores: tolerância a falhas (`83763e2`, 2024-03-12) e correção de fuso (`81c9949`).
- Observação: `scripts/deploy.sh` e `scripts/supervisor/horalis-worker.conf` (adicionados em `e4cf1d5`) não existem mais no código atual; supervisor/orquestração de produção não está visível no repo.

## Questões a responder no ADR

- Por que fila Redis em vez de database/SQS, e a decisão se mantém agora que a produção está na AWS?
- Qual a política de reprocessamento quando a fila acumula falhas (como em 2022-04-12)?
- Quem opera o worker em produção (supervisor, contêiner) e como é monitorado?

## ADRs potenciais relacionados
- `politica-de-idempotencia-e-retry-do-envio-de-lembretes.md`
- `selecao-por-polling-a-cada-10-min-com-fuso-da-clinica.md`

## Notas adicionais
A ata de kickoff (2019-04-02) apenas registra "comando agendado no cron (detalhes com a Juliana)". A decisão de fila só está registrada no Slack.
