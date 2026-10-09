# ADR Potencial: Hospedagem na AWS e processo de deploy (substituiu a VPS única com script SSH e cron)

**Módulo**: INFRA
**Categoria**: Arquitetura / Infraestrutura
**Prioridade**: Must Document (Score: 140)
**Data de Identificação**: 2026-10-09

---

## O que foi identificado

Há duas fases de hospedagem, e a primeira não existe mais no código.

**Fase 1 (2019-2021): VPS única.** Kickoff de 2019-04-02: "uma VPS contratada pelo Rafael; ele cuida do servidor e dos backups", e-mail via SMTP da VPS, lembretes por cron. O commit `48fa4b5` (2019-06-25) adicionou `scripts/deploy.sh` (SSH na VPS: `php artisan down`, `git pull`, `composer install`, `migrate`, `tenants:migrate`, caches, `up`, reload do `php7.3-fpm`) e `scripts/crontab` (`schedule:run` a cada minuto). `e4cf1d5` (2020-02-12) acrescentou worker via `scripts/supervisor/horalis-worker.conf` e `queue:restart` no deploy. O Slack de 2020-05-06 mostra a VPS com disco em 85% e backups manuais por Rafael.

**Fase 2 (2021-06 em diante): AWS.** O Slack #arquitetura (2021-06-07) registra: "a migração da VPS pra AWS terminou na sexta. RDS, ElastiCache, aplicação e worker rodando lá. A VPS desliga dia 30". A preparação aparece em `69b2887` (2021-05-11, `TrustProxies` com `'*'` e logs em `stderr` para rodar atrás de balanceador). Em `cad4e26` (2021-06-08) foram removidos `scripts/deploy.sh`, `scripts/crontab` e o supervisor da VPS, e `d93c79b` (2021-06-15) criou o scheduler como contêiner. O postmortem de 2022-04-12 descreve a topologia: RDS (uma instância), duas instâncias da aplicação atrás de um balanceador, um contêiner de worker, ElastiCache e Sentry; "o deploy é feito pela pipeline: atualiza o código nas duas instâncias, roda `tenants:migrate` e reinicia o worker". **Nenhum arquivo de pipeline ou IaC existe no repositório**, então a configuração real de produção só é conhecida por Slack e postmortem. O motivo da migração (custo, escala, confiabilidade) também não aparece no material.

**Regras operacionais de deploy** (todas informais, só em Slack e postmortem): sem deploy às sextas (2020-02-14, 2020-11-27, 2021-08-13), congelamento no fim do ano (2022-12-15; 2024-12-13: "20/12 a 6/1, só hotfix"), e, após o incidente de 2022-04-12, deploy com migration só depois das 20h, revisão do Rafael e pausa do worker. Esses itens são partes de uma mesma estratégia de entrega (Red Flag 5: consolidar aqui, não em ADRs separados).

## Por que isso pode merecer um ADR

- **Impacto**: todo o sistema e todas as integrações. O custo de infra caiu após a unificação do banco (Slack 2023-10-17), embora o motivo real da unificação fosse outro (ver TENANCY).
- **Trade-offs**: ganho de redundância (2 instâncias) e serviços gerenciados vs custo, dependência de AWS e perda do deploy versionado no repositório.
- **Complexidade**: a pipeline é invisível no repositório; um novo desenvolvedor não consegue reproduzir produção.
- **Conhecimento do time**: todos precisam conhecer a janela de deploy e o fato de a pipeline migrar após trocar o código (causa 2 do postmortem).
- **Temporal**: AWS estável há mais de 5 anos; a VPS durou 2 anos.

## Evidências encontradas no código

### Arquivos e commits
- `scripts/deploy.sh`, `scripts/crontab`, `scripts/supervisor/horalis-worker.conf` - **removidos**; ver `48fa4b5`, `e4cf1d5`, `cad4e26`
- [`app/Http/Middleware/TrustProxies.php`](../../../../../app/Http/Middleware/TrustProxies.php) - em `69b2887` o valor passou a `$proxies = '*'`; hoje está `protected $proxies;` (null). Foi revertido sem explicação no commit `40d1dc9` (upgrade Laravel 10, 2023-07-11). Ponto de atenção: confirmar se produção ainda lê corretamente `X-Forwarded-*` do balanceador (headers incluem `HEADER_X_FORWARDED_AWS_ELB`).
- [`routes/web.php`](../../../../../routes/web.php) linha 12 - `/health` (`3f8c39d`, 2021-06-22), pensada para o balanceador
- [`docs/postmortems/2022-04-12-deploy-travado.md`](../../../../../docs/postmortems/2022-04-12-deploy-travado.md)
- [`.env.example`](../../../../../.env.example) - `LOG_CHANNEL=stderr` (antes `daily`, introduzido em `abd9fdd`; trocado em `69b2887`)

### Evidência de código (removida, `48fa4b5`)
```
php artisan down
git pull
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan tenants:migrate
...
sudo systemctl reload php7.3-fpm
```

### Análise de impacto
- VPS: 2019-04 a 2021-06; AWS: 2021-06 em diante
- Ações do postmortem: `d2bbdab` (log do tempo por schema, 2022-04-20), janelas fora do horário comercial, alerta no Sentry
- Temas: migração, balanceador, health check, janelas e congelamentos

### Alternativas
- Fila SQS foi descartada em 2020 por "não estar na AWS" (ver Redis). Nada sobre alternativas de hospedagem (outros provedores, Kubernetes, PaaS).

## Questões a responder no ADR

- Por que migrar da VPS para a AWS, e por que RDS/ElastiCache e não banco em instância própria?
- Qual a pipeline real (ferramenta, etapas, rollback, quem aprova)? Hoje não há arquivo no repositório.
- A ordem "código antes da migration" ainda vale agora que não há `tenants:migrate`?
- `TrustProxies` em null é intencional?
- As regras de deploy (sexta, congelamentos, 20h) continuam vigentes?

## ADRs potenciais relacionados
- `DATA/postgresql-como-banco-relacional.md`, `redis-fila-cache-e-sessao.md`
- `aplicacao-stateless-multi-instancia-atras-de-balanceador.md`
- `worker-e-scheduler-em-processos-dedicados.md`
- `sentry-e-logs-em-stderr.md`
- `TENANCY`: causa do postmortem de 2022

## Notas adicionais
- Decisão com evidência parcial: substituída (VPS) e vigente (AWS), mas só vigente via memória e Slack.
- Pendências citadas pela DPO (restrição de acesso direto ao banco) não são verificáveis no código.
