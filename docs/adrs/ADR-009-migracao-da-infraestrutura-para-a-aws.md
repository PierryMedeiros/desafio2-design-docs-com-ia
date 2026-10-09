# ADR-009: Migração da infraestrutura da VPS para a AWS com serviços gerenciados

- **Status:** Accepted
- **Data:** 2021-06-07
- **Decisores:** **Needs Input:** as fontes não dizem quem decidiu. O anúncio é do Rafael Lima (CTO).
- **Relações:** nenhuma relação de substituição, emenda ou dependência. A [ADR-010](ADR-010-anexos-no-s3-com-url-pre-assinada.md) depende desta.

## Contexto e problema

De 2019 a meados de 2021, a produção rodou numa VPS contratada pelo Rafael, que cuidava do servidor e dos backups (ata do kickoff, `contexto/atas/2019-04-02-kickoff-tecnico.md`). Na VPS ficavam nginx, PHP-FPM, PostgreSQL e cron no mesmo servidor, com deploy por `scripts/deploy.sh` (`docs/ARQUITETURA.md`, `48fa4b5`). Em 2020 vieram o Redis e o worker, também na VPS ([ADR-005](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md)).

As fontes registram alguns fatos da época, mas nenhuma os liga à migração:

- Em 2020-02-12, Rafael descartou o SQS porque "a gente nem tá na aws" e disse que, "se um dia a gente for pra lá", a decisão seria revista.
- O Postgres "já fica apertado na VPS de tarde" (2020-02-12).
- O disco da VPS chegou a 85% por causa de dumps antigos, em 2020-05-06.

O primeiro registro da decisão é o anúncio, já concluída:

> [2021-06-07 09:35] Rafael Lima: e pra registrar aqui: a migração da VPS pra AWS terminou na sexta. RDS, ElastiCache, aplicação e worker rodando lá. a VPS desliga dia 30

**Needs Input:** o problema que motivou a migração não está nas fontes. O `contexto/LEIA-ME.md` avisa que "há decisões que aparecem só depois de tomadas, sem a discussão que levou até elas", e esta é uma delas. Os fatos acima não podem ser tratados como o motivo sem confirmação de quem participou.

## Opções consideradas

1. **AWS com serviços gerenciados:** RDS para o PostgreSQL, ElastiCache para o Redis, e aplicação e worker na AWS.
2. **Continuar na VPS.**
3. **Needs Input:** as fontes não registram se outros provedores ou formatos foram avaliados (outra nuvem, PaaS, banco gerenciado de outro fornecedor) nem os critérios de comparação.

## Decisão

Opção 1. A produção foi para a AWS, com RDS, ElastiCache, aplicação e worker. A VPS foi desligada em 2021-06-30 (Slack, 2021-06-07).

**Needs Input:** por que AWS, por que serviços gerenciados e por que naquele momento?

O repositório mostra a preparação e a limpeza em volta da data:

- `69b2887` (2021-05-11): "configuração para rodar atrás do load balancer", com `TrustProxies` e logs em `stderr`.
- `cad4e26` (2021-06-08): remove `scripts/deploy.sh`, o crontab e a configuração do supervisor da VPS.
- `d93c79b` (2021-06-15): scheduler em contêiner próprio (`schedule:work`), no lugar do crontab.
- `3f8c39d` (2021-06-22): rota `GET /health`.

Pelo postmortem de 2022, a produção na AWS tinha um RDS de instância única, duas instâncias da aplicação atrás de um balanceador, um contêiner de worker, ElastiCache para fila e sessões, Sentry e um deploy por pipeline (`docs/postmortems/2022-04-12-deploy-travado.md`). Nada disso está versionado no repositório.

## Consequências

### Positivas

- Banco e Redis passaram a ser serviços gerenciados. O e-mail da DPO confirma criptografia de disco ativa no banco na AWS (`contexto/emails/2022-05-03-dpo-criptografia.md`).
- Abriu caminho para o S3 nos anexos ([ADR-010](ADR-010-anexos-no-s3-com-url-pre-assinada.md)) e para a segunda instância da aplicação atrás do balanceador (2021-08).
- O deploy deixou de ser um script por SSH e passou a uma pipeline.

### Negativas

- A infraestrutura e a pipeline de produção ficaram fora do repositório. Não dá para reproduzir nem conferir a produção pelo código.
- O RDS continuou numa instância só, e o limite de conexões do RDS aparece como preocupação em 2022 (`contexto/atas/2022-05-20-reuniao-tenancy.md`).
- A premissa que descartou o SQS na [ADR-005](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md) deixou de valer, e as fontes não registram se a escolha foi revista.
- **Needs Input:** custo antes e depois da migração e trade-offs avaliados.

## Evidências

- Commits: `69b2887` (preparação para o balanceador), `cad4e26` (remove o deploy da VPS), `d93c79b` (scheduler em contêiner), `3f8c39d` (health check), `48fa4b5` (script de deploy da VPS, de 2019).
- Arquivos: `scripts/deploy.sh` (existe no histórico até `cad4e26`), `docker-compose.yml` (serviço `scheduler`), `app/Http/Middleware/TrustProxies.php`, `routes/web.php` (`/health`).
- Rastros: `contexto/slack/arquitetura.md` (2020-02-12, 2020-05-06, 2021-06-07), `contexto/atas/2019-04-02-kickoff-tecnico.md` (hospedagem em VPS), `docs/ARQUITETURA.md` (seção "Infraestrutura"), `docs/postmortems/2022-04-12-deploy-travado.md` (contexto da infraestrutura), `contexto/LEIA-ME.md`.
