# Mapeamento da Arquitetura do Código

> Fase 1 do processo de identificação de ADRs. Gerado em 2026-10-09 sobre a branch `aluno-teste`.
> Fontes: código, `docs/ARQUITETURA.md`, `docs/postmortems/`, `contexto/` (atas, e-mail, Slack) e histórico git (2019-2025, 164 commits).

## Visão Geral do Projeto

- **Nome**: Horalis
- **Propósito**: sistema de agendamento de consultas para clínicas (multi-clínica / SaaS). Recepção e profissionais usam um painel web; pacientes usam um app mobile (via API) e recebem lembretes por WhatsApp/SMS.
- **Tipo**: monólito Laravel, com telas renderizadas no servidor (Blade) e API REST versionada no mesmo projeto.
- **Linguagem / framework**: PHP 8.2, Laravel 10 (`laravel/framework ^10.10`).
- **Escala citada no contexto**: 380 clínicas em 2022, previsão de 600+; maior clínica com cerca de 30 mil pacientes; app do paciente na loja (versão 3.0 em 2023, ainda em `/api/v1`).
- **Equipe**: quase todas as decisões de 2019-2024 foram tomadas por pessoas que já saíram (Rafael Lima, CTO, saiu em 06/2024). O material de contexto é incompleto (ver "Notas de Contexto").

## Stack Tecnológica

| Camada | Tecnologia | Evidência |
|---|---|---|
| Linguagem / runtime | PHP 8.2 (php-fpm) | `composer.json`, `docker/Dockerfile` |
| Framework | Laravel 10 (histórico: 5.8, 6, 8, 9, 10) | `composer.json`; commits de upgrade 2019-12, 2021-01, 2022-09, 2023-07 |
| Views | Blade + CSS simples (`public/css/painel.css`), sem build JS | `resources/views`, `public/css` |
| Banco de dados | PostgreSQL 16 (`pdo_pgsql`) | `docker-compose.yml`, `config/database.php` |
| Cache / fila / sessão | Redis 7.2 via `predis` | `.env.example` (`CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER` = redis), `composer.json` |
| Armazenamento de arquivos | S3 (AWS); `adobe/s3mock` no ambiente local; URL pré-assinada | `config/filesystems.php`, `app/Anexos/ArmazenamentoAnexos.php`, `league/flysystem-aws-s3-v3` |
| Autenticação | Sessão (painel, usuários da clínica) + Laravel Sanctum (tokens de paciente na API) | `config/auth.php`, `config/sanctum.php`, `routes/api.php` |
| Mensageria externa | WhatsApp Cloud API (Meta) como canal principal; SMS Twilio como fallback | `app/Lembretes/Canais`, `config/lembretes.php` |
| Observabilidade | Sentry (`sentry-laravel`), logs em stderr | `config/sentry.php`, `.env.example` |
| Servidor web | nginx 1.27 + php-fpm | `docker/nginx/default.conf` |
| Contêineres | Docker Compose: nginx, app, worker, scheduler, postgres, redis, s3 | `docker-compose.yml`, `docker/` |
| Qualidade | PHPUnit 10, Laravel Pint | `phpunit.xml`, `pint.json` |
| Produção (segundo o contexto, não visível no repo) | AWS: RDS Postgres, ElastiCache Redis, duas instâncias da aplicação atrás de balanceador, contêiner de worker | Slack #arquitetura (2021-06, 2021-08), postmortem 2022-04-12 |

## Notas de Contexto

**Arquivos de contexto analisados**: `contexto/LEIA-ME.md`, `contexto/atas/2019-04-02-kickoff-tecnico.md`, `contexto/atas/2022-05-20-reuniao-tenancy.md`, `contexto/emails/2022-05-03-dpo-criptografia.md`, `contexto/slack/arquitetura.md`, `contexto/slack/geral.md`, além de `docs/ARQUITETURA.md` e `docs/postmortems/2022-04-12-deploy-travado.md`.

**Principais insights**:

- **Padrões arquiteturais citados**: monólito modular por pastas, multi-tenancy, event/listener, job em fila, Strategy/Chain of Responsibility para canais de lembrete (interface `CanalLembrete` + fallback), serviço de domínio (`Disponibilidade`).
- **Linha do tempo das decisões** (contexto + git):
  - 2019-04: monólito Laravel + Blade; Postgres; um schema por clínica (orientação do advogado, LGPD); alternativa Node + React descartada por falta de experiência do time e prazo.
  - 2020-02: lembretes passam de envio síncrono no cron para fila Redis + worker dedicado (duplicidade de lembretes quando a execução passou de 10 min); `database` e `sqs` descartados.
  - 2020-03: lembrete por SMS (Twilio) atrás da interface `CanalLembrete`, para permitir novos canais.
  - 2021-02/03: API REST `/api/v1` no monólito, Sanctum (token opaco por dispositivo) em vez de JWT.
  - 2021-06: migração da VPS para AWS (RDS, ElastiCache).
  - 2021-08: anexos vão do disco local para S3 com URL pré-assinada, e sessões vão para Redis, após falha ao subir a segunda instância.
  - 2022-04: incidente do deploy de 5h30 (380 schemas).
  - 2022-05: criptografia de campo (CPF e notas clínicas) + `cpf_hash` (HMAC-SHA256), exigência da DPO como condição para unificar.
  - 2022-05/06: decisão e execução de schema único com `tenant_id` + escopo global (alternativas: migração paralela, banco por clínica, RLS).
  - 2023-05/06: POC de busca com Scout + Meilisearch, revertida.
  - 2023-09: WhatsApp (Cloud API direta) como canal principal, com fallback para SMS e opt-in do paciente.
  - 2023-12: canal de e-mail removido.
  - 2024-03: ajuste de timeout, tries e backoff do job de lembrete após instabilidade do Twilio.
  - 2025-01: extração do serviço `Disponibilidade`.
- **Módulos documentados**: painel, API v1, lembretes, anexos, tenancy.
- **Tecnologias documentadas versus código**: ver discrepâncias abaixo.

**Discrepâncias entre documentação e código** (importante: `docs/ARQUITETURA.md` está desatualizado, última atualização em julho/2019):

1. `docs/ARQUITETURA.md` descreve **um schema por clínica**, `search_path`, middleware `DefinirSchemaTenant`, `tenants:migrate` e `tenants:criar`. No código atual não existem: há schema único, coluna `tenant_id`, `BelongsToTenant`, `TenantScope`, `TenantContext` e `IdentificarTenant` (migrações `2022_06_09` e `2022_06_28`). O próprio documento serve de evidência do estado anterior e não deve ser lido como o estado atual.
2. O documento descreve lembrete por **e-mail, síncrono**, comando `lembretes:enviar`. Hoje: `lembretes:enfileirar` + job `EnviarLembreteAgendamento` na fila `notificacoes`, canais WhatsApp > SMS; e-mail removido (2023-12).
3. O documento descreve anexos em **disco local** e sessão em **arquivo**. Hoje: S3 com `temporaryUrl` de 10 minutos e sessão em Redis.
4. O documento cita **mailhog** e `scripts/deploy.sh` (ambos removidos; o script de deploy da VPS saiu em 2021-06) e produção em **VPS única** (hoje AWS, segundo o Slack).
5. O documento afirma "pacientes não acessam o sistema" e prevê **app em React Native com API GraphQL** e microsserviço de agenda. Realidade: existe app do paciente, mas a API é **REST** (`/api/v1`) no próprio monólito; GraphQL e microsserviço nunca foram implementados.
6. O documento lista a "Laravel 5.8". Hoje é Laravel 10.
7. **Narrativa divergente sobre a unificação do banco**: em #geral (2023-10-17), Helena afirma que "a unificação do banco foi pra economizar no RDS". A ata de 2022-05-20 registra que a economia foi efeito colateral e que o motivo real foi o deploy lento e o estado misto das migrations (Rafael pediu para não vender como economia). O ADR deve registrar o motivo real, com base na ata e no postmortem.
8. O comentário no Slack (2021-08) cita **minio** no compose de dev; o compose atual usa `adobe/s3mock` (troca posterior; o motivo não está documentado).
9. Há pontos **sem motivo registrado** (candidatos a "sempre foi assim", ver `LEIA-ME.md`): `TenantScope` não aplica filtro quando o contexto não está ativo (comportamento aberto por padrão, relevante para jobs/comandos); `IdentificarTenant` usa `tenant_id` do usuário autenticado, enquanto o Slack de 2021 descrevia o header `X-Clinica` (superado em 2022-07, "tenant identificado pelo dono do token"); canal de lembretes único configurado por `.env` para todas as clínicas (decisão de 2020 "por enquanto simples").
10. Do e-mail da DPO: a criptografia em repouso do RDS foi explicitamente tratada como insuficiente; a chave do `cpf_hash` deve ficar separada do banco (no código, `CPF_HASH_KEY` via env). Pendências citadas e não verificáveis no código: atualização do RIPD, restrição de acesso direto ao banco.

## Módulos do Sistema

### Índice de Módulos

1. **TENANCY** - Multi-tenancy: schema único com `tenant_id`, escopo global e contexto por requisição.
2. **AUTH** - Autenticação: sessão no painel e Sanctum na API de pacientes.
3. **API** - API REST versionada `/api/v1` para o app do paciente.
4. **PAINEL** - Painel web em Blade (agenda, agendamentos, pacientes, profissionais, lista de espera).
5. **AGENDA** - Regras de disponibilidade e conflito de horários (serviço de domínio).
6. **LEMBRETES** - Lembretes assíncronos: comando, fila, job, canais com fallback.
7. **ANEXOS** - Anexos de agendamento em S3 com URL temporária.
8. **PRIVACIDADE** - Proteção de dados sensíveis: criptografia de campo e hash de CPF.
9. **DATA** - Persistência: PostgreSQL, migrations, modelos Eloquent, seeders.
10. **EVENTOS** - Eventos de domínio e listeners (status do agendamento, lista de espera).
11. **INFRA** - Contêineres, compose, nginx, entrypoint, observabilidade, CI de testes.

### TENANCY: Multi-tenancy (schema único + tenant_id)

**Propósito**: isolar os dados de cada clínica dentro de um único schema, por filtro de aplicação.
**Localização**: `app/Tenancy/*`, `app/Http/Middleware/IdentificarTenant.php`, `app/Models/Tenant.php`, migrações `2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php` e `2022_06_28_113000_remove_schema_from_tenants_table.php`.
**Componentes principais**: trait `BelongsToTenant` (escopo global + preenchimento no `creating`), `TenantScope`, `TenantContext` (estado da requisição), middleware `tenant` (alias em `app/Http/Kernel.php`), `Tenant::agora()` (fuso por clínica).
**Tecnologias**: Eloquent global scopes, container do Laravel.
**Dependências**: internas: AUTH, DATA, todos os modelos de clínica. Externas: nenhuma.
**Padrões**: Shared Schema multi-tenancy, Global Scope, Request-scoped context.
**Arquivos-chave**: `app/Tenancy/TenantScope.php`, `app/Tenancy/TenantContext.php`, `app/Http/Middleware/IdentificarTenant.php`, `tests/Feature/Tenancy/*`.
**Contexto histórico**: reverteu a decisão de 2019 (schema por clínica) após o postmortem de 2022-04-12 e a ata de 2022-05-20; opções (a) migração paralela, (b) banco por clínica, (c) RLS foram descartadas. Mitigações: testes de isolamento e revisão de `withoutGlobalScopes`/`DB::table`. Condicionada à criptografia de campo (PRIVACIDADE). Ponto de atenção: o escopo é omitido quando não há contexto ativo (jobs/comandos).
**Escopo**: Pequeno em arquivos (cerca de 8), mas transversal (todo o sistema).

### AUTH: Autenticação e autorização

**Propósito**: login da equipe (sessão) e de pacientes (token).
**Localização**: `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Api/V1/AuthController.php`, `config/auth.php`, `config/sanctum.php`, `config/session.php`, `app/Models/User.php`, `app/Models/Paciente.php`.
**Componentes principais**: login por e-mail/senha com sessão no Redis; `POST /api/v1/auth/token` com Sanctum, um token por dispositivo; `personal_access_tokens` com `expires_at` (migração 2023-07).
**Tecnologias**: Laravel Sanctum 3, driver de sessão Redis.
**Dependências**: TENANCY (a clínica vem do usuário/token), DATA.
**Padrões**: Token opaco persistido (revogável) em vez de JWT; sessão compartilhada para instâncias múltiplas.
**Arquivos-chave**: `routes/api.php`, `routes/web.php`, `config/session.php`.
**Contexto histórico**: JWT (tymon) descartado em 2021-02 pela dificuldade de revogação; sessão em arquivo causou deslogamentos com 2 instâncias em 2021-08, migrada para Redis.
**Escopo**: Pequeno (cerca de 8 arquivos).

### API: API REST v1 para o app do paciente

**Propósito**: expor horários, agendamentos e autenticação ao app mobile.
**Localização**: `app/Http/Controllers/Api/V1/*`, `app/Http/Resources/AgendamentoResource.php`, `routes/api.php`.
**Componentes principais**: `AuthController`, `HorarioController`, `AgendamentoController` (listar com paginação, criar, cancelar/reagendar); respostas `409` quando o horário foi ocupado.
**Tecnologias**: Laravel API resources, Sanctum.
**Dependências**: AUTH, TENANCY, AGENDA, DATA.
**Padrões**: REST com versão na URL (`/api/v1`) por não ser possível forçar atualização do app; contrato estável (v1 continua em uso pelo app 3.0 em 2023).
**Arquivos-chave**: `routes/api.php`, `tests/Feature/Api/*`.
**Contexto histórico**: criada em 2021-03 no mesmo monólito (sem serviço separado); a visão de GraphQL no `ARQUITETURA.md` nunca foi implementada.
**Escopo**: Pequeno (cerca de 6 arquivos + testes).

### PAINEL: Painel web (Blade)

**Propósito**: interface da recepção/profissionais.
**Localização**: `app/Http/Controllers/*Controller.php`, `resources/views/*`, `public/css/painel.css`, `routes/web.php`.
**Componentes principais**: agenda por dia/profissional, novo agendamento, mudança de status, pacientes (busca por nome e CPF), profissionais, lista de espera, rota `/health`.
**Tecnologias**: Blade server-side, sem SPA e sem build JS.
**Dependências**: AUTH, TENANCY, AGENDA, ANEXOS, PRIVACIDADE.
**Padrões**: Server-Side Rendering em monólito.
**Contexto histórico**: Node + React descartado em 2019; busca de pacientes via Scout/Meilisearch testada e revertida em 2023-06 (busca continua no banco).
**Escopo**: Médio (cerca de 20 arquivos).

### AGENDA: Disponibilidade e conflitos

**Propósito**: calcular horários livres, detectar conflitos, aplicar bloqueios e feriados.
**Localização**: `app/Agenda/Disponibilidade.php`, `app/Models/{Disponibilidade,Bloqueio,Servico,Profissional}.php`, `config/feriados.php`.
**Componentes principais**: `horariosLivres`, `conflita`, `diaBloqueado`; feriados como configuração estática; fuso horário por clínica.
**Dependências**: DATA, TENANCY. Consumido por PAINEL e API.
**Padrões**: Domain Service compartilhado entre painel e API (refatoração de 2025-01/02).
**Escopo**: Pequeno (cerca de 6 arquivos). Em grande parte regra de negócio, só o serviço compartilhado é candidato a decisão arquitetural.

### LEMBRETES: Lembretes assíncronos e canais

**Propósito**: avisar pacientes sobre consultas com 24 h de antecedência, reduzindo faltas.
**Localização**: `app/Lembretes/*`, `app/Jobs/EnviarLembreteAgendamento.php`, `app/Console/Commands/EnfileirarLembretes.php`, `app/Console/Kernel.php`, `config/lembretes.php`, `config/queue.php`.
**Componentes principais**: comando `lembretes:enfileirar` a cada 10 min com `withoutOverlapping`; trava por agendamento via `Cache::add`; job na fila `notificacoes` (`tries=3`, `timeout=30`, `backoff=[30,120,300]`); interface `CanalLembrete`; `CanalComFallback` (WhatsApp > SMS); `FabricaDeCanais`; `LogCanal` para desenvolvimento; opt-in `aceita_whatsapp`.
**Tecnologias**: Redis queue, worker e scheduler em contêineres próprios, WhatsApp Cloud API (HTTP direto, sem BSP), Twilio SMS.
**Dependências**: DATA, TENANCY, Redis; externas: Meta, Twilio.
**Padrões**: Producer/consumer via fila, Strategy + fallback chain, idempotência por lock e por `lembrete_enviado_em`.
**Contexto histórico**: nasceu síncrono por e-mail (2019), gerou duplicidades em 2020-02 e foi para a fila; SMS em 2020-03 para atacar faltas (22% numa clínica); WhatsApp em 2023-09 (SMS caro e menos lido); e-mail removido em 2023-12; ajuste de timeout/retry em 2024-03. O canal é global via `.env`, não por clínica.
**Escopo**: Médio (cerca de 15 arquivos).

### ANEXOS: Anexos em S3

**Propósito**: guardar exames/documentos anexados ao agendamento.
**Localização**: `app/Anexos/ArmazenamentoAnexos.php`, `app/Http/Controllers/AnexoController.php`, `app/Models/Anexo.php`, `config/filesystems.php`.
**Componentes principais**: caminho `tenants/{id}/agendamentos/{id}/{uuid}.ext`, `Storage::disk('s3')`, `temporaryUrl` com validade de 10 minutos, `endpoint_publico` para URLs assinadas em ambiente local.
**Tecnologias**: S3, Flysystem.
**Dependências**: TENANCY, AUTH, INFRA (bucket privado, role restrita, segundo o Slack).
**Contexto histórico**: disco local causou 404 intermitentes com 2 instâncias (2021-08-10); migrados cerca de 41 mil arquivos. Bucket com bloqueio de acesso público e criptografia (configuração fora do repo).
**Escopo**: Pequeno (cerca de 5 arquivos).

### PRIVACIDADE: Criptografia de campo e hash de CPF

**Propósito**: proteger dados sensíveis (LGPD) de forma independente do isolamento físico.
**Localização**: `app/Criptografia/HashCpf.php`, `app/Models/Paciente.php` (cast `encrypted` em `cpf`), `app/Models/Agendamento.php` (cast `encrypted` em `notas_clinicas`), `app/Console/Commands/CriptografarDadosPacientes.php`, `app/Rules/Cpf.php`, migrações `2022_05_10` e `2022_05_12`.
**Componentes principais**: cast `encrypted` (APP_KEY), `cpf_hash` HMAC-SHA256 com `CPF_HASH_KEY` separada, busca de paciente por hash.
**Dependências**: DATA, TENANCY (pré-requisito da unificação).
**Padrões**: Application-level field encryption, blind index (hash com chave).
**Contexto histórico**: exigida pela DPO (e-mail de 2022-05-03) antes da unificação; em produção desde 2022-05-17.
**Escopo**: Pequeno (cerca de 6 arquivos); impacto alto.

### DATA: Persistência

**Propósito**: modelo relacional e evolução do schema.
**Localização**: `app/Models/*`, `database/migrations/*`, `database/seeders/*`, `database/factories/*`, `config/database.php`.
**Tecnologias**: PostgreSQL 16, Eloquent ORM, migrations versionadas (2014-2023), seeders de duas clínicas de exemplo.
**Padrões**: Active Record; schema único público com `tenant_id` em todas as tabelas de clínica.
**Contexto histórico**: Postgres desde 2019 (13 em 2022, 15 em 2023, 16 em 2024); RDS em produção desde 2021. Migrações de 2019-2022 deixam o rastro de `tenants:migrate`.
**Escopo**: Médio (cerca de 45 arquivos).

### EVENTOS: Eventos de domínio

**Propósito**: reagir a mudanças de status do agendamento.
**Localização**: `app/Events/AgendamentoStatusAlterado.php`, `app/Listeners/*`, `app/Providers/EventServiceProvider.php`.
**Componentes principais**: `RegistrarMudancaDeStatus` (log) e `AvisarListaEspera` (marca como avisado; hoje apenas registra em log, sem envio real ao paciente).
**Padrões**: Observer / Domain Events síncronos.
**Contexto histórico**: eventos em 2022-10; lista de espera em 2023-08/11. Listeners são síncronos (não usam `ShouldQueue`).
**Escopo**: Pequeno (cerca de 4 arquivos).

### INFRA: Infraestrutura e operação

**Propósito**: empacotar, subir e observar a aplicação.
**Localização**: `docker-compose.yml`, `docker/*`, `.env.example`, `config/{logging,sentry,cache,queue,session}.php`, `app/Exceptions/Handler.php`, `phpunit.xml`.
**Componentes principais**: serviços `nginx`, `app` (php-fpm), `worker`, `scheduler`, `postgres`, `redis`, `s3`; entrypoint que espera o banco, migra e roda seed; imagens com tag fixa; banco de teste `horalis_test`; Sentry; logs em stderr; `TrustProxies` para rodar atrás do balanceador.
**Dependências externas**: AWS (RDS, ElastiCache, S3, balanceador), Sentry.
**Contexto histórico**: VPS com cron (2019) para AWS (2021-06); scheduler em contêiner próprio (2021-06); healthcheck (2021-06); regras operacionais informais: sem deploy às sextas, congelamentos de fim de ano, deploy de migration fora do horário comercial (pós-postmortem).
**Escopo**: Pequeno (cerca de 12 arquivos). A configuração real de produção não está no repositório.

## Preocupações Transversais

- **Isolamento de dados e LGPD**: combinação de TENANCY (filtro por aplicação) + PRIVACIDADE (criptografia de campo) + testes de isolamento (`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`, `tests/Feature/CriptografiaTest.php`). O risco residual do escopo global é conhecido e documentado na ata de 2022.
- **Processamento assíncrono**: fila Redis (`notificacoes`, `default`), worker e scheduler em contêineres separados; sessão e cache também no Redis, o que o torna dependência crítica.
- **Observabilidade**: Sentry (desde 2019), log estruturado em eventos de status, alertas definidos após o postmortem.
- **Integrações externas**: Meta WhatsApp Cloud API, Twilio, S3, Sentry, RDS/ElastiCache (nível de infraestrutura).
- **Estratégia de evolução da API**: versão na URL, contrato v1 mantido por compatibilidade com apps instalados.
- **Processo de deploy**: sem arquivo de pipeline no repositório; práticas descritas apenas em Slack e postmortem.
- **Testes**: suíte de feature/unit com PHPUnit; fila `sync` e cache `array` nos testes; Postgres real (`horalis_test`).

## Candidatos Iniciais para a Fase 2 (indicativos, sem pontuação)

Sugestão de ordem de análise: **TENANCY**, **PRIVACIDADE**, **LEMBRETES**, **API/AUTH**, **ANEXOS**, **INFRA**, **DATA**, **PAINEL**. Decisões descartadas ou revertidas com registro (Meilisearch/Scout, RLS, banco por clínica, JWT, fila `database`, e-mail como canal) e a divergência de motivos sobre a unificação do banco merecem atenção na redação de qualquer ADR.
