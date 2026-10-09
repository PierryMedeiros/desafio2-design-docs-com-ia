# ADR-005: Fila Redis com worker dedicado para os lembretes

- **Status:** Accepted
- **Data:** 2020-02-12
- **Decisores:** Rafael Lima (CTO), Marcos Teixeira, Juliana Prado
- **Relações:**
  - supersedes [ADR-004: Envio síncrono de lembretes pelo comando agendado](ADR-004-envio-sincrono-de-lembretes-pelo-comando-agendado.md)

## Contexto e problema

Em fevereiro de 2020, pacientes da Clínica Movimento receberam o mesmo lembrete duas ou três vezes. O `lembretes:enviar` ([ADR-004](ADR-004-envio-sincrono-de-lembretes-pelo-comando-agendado.md)) levou 14 minutos na execução das 18h de 2020-02-10. Como o cron chamava o comando a cada 10 minutos, a execução seguinte começava antes da anterior terminar, e as duas pegavam os mesmos agendamentos. O envio era síncrono, com uma conexão SMTP por e-mail, e o SMTP da VPS ficava lento à tarde (`contexto/slack/arquitetura.md`, 2020-02-11).

Rafael sugeriu `withoutOverlapping` como paliativo. Juliana respondeu que isso resolveria a duplicação mas não o tempo: a execução seguinte só pularia e os lembretes atrasariam. O time decidiu tirar o envio de dentro do comando, mantendo também o `withoutOverlapping`.

## Opções consideradas

1. **Só `withoutOverlapping` no schedule.** Resolve a duplicação, mas os lembretes atrasam.
2. **Fila com driver `database`.** É a mais fácil, porque só precisa da tabela `jobs`.
3. **Fila no Amazon SQS.**
4. **Fila no Redis com worker dedicado.**

## Decisão

Opção 4, com o `withoutOverlapping` da opção 1 junto. Um contêiner de Redis na VPS e o worker num contêiner separado, com supervisor reiniciando se cair. O comando vira `lembretes:enfileirar`: ele só seleciona os lembretes que precisam sair e despacha um job `EnviarLembreteAgendamento` por lembrete na fila `notificacoes`, separada da `default` "pq vai ter outras coisas lá". Uma trava no cache por agendamento impede despachar o mesmo lembrete duas vezes se o worker atrasar (Slack, 2020-02-12).

Por que as outras foram descartadas (Slack, 2020-02-12):

- **`database`:** Rafael não queria o worker fazendo polling e lock na tabela `jobs` o tempo todo, porque isso pesaria no Postgres, que já ficava apertado na VPS à tarde. Também havia a dúvida de em qual schema a tabela ficaria ([ADR-003](ADR-003-um-schema-por-clinica-no-banco.md)).
- **SQS:** o time não estava na AWS. Rafael: "não vou abrir conta na aws só pra ter fila. se um dia a gente for pra lá a gente revê".

Implementação:

- `e4cf1d5`: Redis e worker no compose, supervisor.
- `2fb26b9`: job `EnviarLembreteAgendamento`.
- `d79772d`: `lembretes:enviar` vira `lembretes:enfileirar`, com `withoutOverlapping` no schedule.
- `6252025`: tabela `failed_jobs`.
- `8142e75`: `Cache::add` por agendamento para evitar duplicado na fila.

A fila entrou em produção em 2020-02-17.

## Consequências

### Positivas

- O `lembretes:enfileirar` passou a rodar em 3 segundos, e não houve mais reclamação de lembrete duplicado (Slack, 2020-02-17 e 2020-02-18).
- O envio ficou isolado num processo que escala e reinicia separado da aplicação.
- A fila serviu de base para o canal de SMS e depois para o de WhatsApp ([ADR-006](ADR-006-lembretes-por-sms-com-twilio-atras-da-interface-canallembrete.md), [ADR-014](ADR-014-whatsapp-cloud-api-como-canal-principal-com-fallback-para-sms.md)).
- No incidente de 2022-04-12, os jobs que falharam ficaram no Redis e puderam ser reprocessados com `queue:retry` (`docs/postmortems/2022-04-12-deploy-travado.md`).
- O Redis já disponível foi reaproveitado depois para cache e sessão ([ADR-011](ADR-011-sessoes-do-painel-no-redis.md)).

### Negativas

- Mais dois componentes para operar: o Redis e o worker.
- Timeouts e tentativas precisam ser coerentes entre o job, o worker e a conexão. Em 2024-03-12, com o Twilio instável, o job (timeout de 120s, 5 tentativas) era morto pelo worker (timeout de 60s) e voltava para a fila depois de o SMS já ter saído, e houve lembretes repetidos. O ajuste para `$timeout = 30`, `$tries = 3`, `$backoff = [30, 120, 300]` e worker com `--tries=3 --timeout=60`, mantendo `retry_after` em 90, entrou em `83763e2`.
- Durante deploys com migration, os jobs falham para as clínicas não migradas e se acumulam. Em 2022-04-12 o worker foi pausado na mão.

## Observação sobre a premissa do SQS

O SQS foi descartado por um motivo que deixou de valer em 2021, quando a infraestrutura foi para a AWS ([ADR-009](ADR-009-migracao-da-infraestrutura-para-a-aws.md)). O Redis foi para o ElastiCache e continuou como fila. **Needs Input:** as fontes não registram se a escolha entre Redis e SQS foi revista na migração, como o Rafael tinha dito que faria.

## Evidências

- Commits: `e4cf1d5`, `2fb26b9`, `d79772d`, `6252025`, `8142e75`, `83763e2` (ajuste de timeouts).
- Arquivos: `app/Jobs/EnviarLembreteAgendamento.php`, `app/Console/Commands/EnfileirarLembretes.php`, `app/Console/Kernel.php`, `config/queue.php`, `docker-compose.yml` (serviços `worker` e `redis`).
- Rastros: `contexto/slack/arquitetura.md` (2020-02-11 a 2020-02-18 e 2024-03-12), `docs/postmortems/2022-04-12-deploy-travado.md`.
