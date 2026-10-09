# ADR Potencial: Observabilidade com Sentry e logs em stderr

**Módulo**: INFRA
**Categoria**: Observabilidade
**Prioridade**: Consider (Score: 90)
**Data de Identificação**: 2026-10-09

---

## O que foi identificado

O monitoramento de erros é feito pelo Sentry desde 2019-09-10 (`2bc64ae`, `config/sentry.php` e integração no `Handler`). Sentry foi decisivo no incidente de 2022-04-12 (detectou em 2 min e agrupou os erros) e o postmortem criou um alerta para "mesmo erro com mais de 500 eventos em 5 minutos". A biblioteca foi atualizada para `sentry-laravel 4` em `4e03756` (2024-09-24). O DSN vem de `SENTRY_LARAVEL_DSN` e o `.env.example` mantém `SENTRY_TRACES_SAMPLE_RATE=0`.

Logs: o canal passou de `stack` para `daily` em `abd9fdd` (2019-08-20, arquivo rotativo na VPS) e para `stderr` em `69b2887` (2021-05-11, preparo para contêiner/balanceador, sem disco local). Há também registro de mudança de status de agendamento em log (`9c5c3ab`, 2022-10-06). Não há agregador de logs no repositório (o destino do stderr em produção é desconhecido), nem métricas ou tracing (traces em 0).

Decisão clara é Sentry e logs em stderr; o que falta registrar é por que Sentry e qual o destino/retention do stderr.

## Por que isso pode merecer um ADR

- **Impacto**: todo o sistema; alertas são o principal sinal de incidente.
- **Trade-offs**: SaaS de terceiros com dados potencialmente sensíveis (stack traces com PII?) em contexto de LGPD.
- **Conhecimento do time**: quem faz plantão precisa saber onde olhar.
- **Temporal**: estável há 7 anos.

## Evidências encontradas no código

- [`config/sentry.php`](../../../../../config/sentry.php), [`app/Exceptions/Handler.php`](../../../../../app/Exceptions/Handler.php) (linha 6, `Sentry\Laravel\Integration`)
- [`.env.example`](../../../../../.env.example) - `LOG_CHANNEL=stderr`, `SENTRY_LARAVEL_DSN`
- [`docs/postmortems/2022-04-12-deploy-travado.md`](../../../../../docs/postmortems/2022-04-12-deploy-travado.md)

### Análise de impacto
- Introduzido: 2019-09-10 (`2bc64ae`)
- Logs: `abd9fdd` (2019-08), `69b2887` (2021-05)
- Temas: alertas, postmortem, logs em contêiner

### Alternativas
- Nenhuma registrada.

## Questões a responder no ADR

- Existe scrubbing de dados sensíveis (CPF, notas clínicas) nos eventos enviados ao Sentry? (importante pela LGPD)
- Para onde vai o stderr em produção e qual a retenção?
- As regras de alerta pós-postmortem estão configuradas?

## ADRs potenciais relacionados
- `aws-como-hospedagem-e-pipeline-de-deploy.md`
- `PRIVACIDADE`

## Notas adicionais
- Pontuação baseada em serviço externo crítico (base 70) mais dimensões baixas; alternativamente poderia ser consolidado ao ADR de hospedagem.
