# HLD: Horalis no estado atual

- **Escopo:** a Horalis como ela está no HEAD do repositório (último commit de código: `989d7cc`, 2025-11-18).
- **Base:** código, `docker-compose.yml`, histórico do git e os rastros em `contexto/`, `docs/ARQUITETURA.md` e `docs/postmortems/`.
- **Regra deste documento:** só descreve o que está no código de hoje. O que vem só dos rastros (produção na AWS, por exemplo) está marcado como tal. O `docs/ARQUITETURA.md` de 2019 está desatualizado em quase tudo e não foi usado como retrato do presente (veja a seção 11).
- **Decisões:** cada escolha que tem ADR vigente aponta para ela. O índice das ADRs está em [`docs/adrs/README.md`](adrs/README.md).

## 1. Objetivo técnico

A Horalis é um SaaS multi-clínica de agendamento. O sistema precisa:

- dar à equipe de cada clínica (admin, recepção e profissional) um painel web para agenda, pacientes, profissionais, anexos e lista de espera;
- dar ao app do paciente uma API estável para ver horários livres, marcar, remarcar, listar e cancelar consultas;
- mandar lembretes automáticos das consultas, por WhatsApp com fallback para SMS, para reduzir faltas;
- manter os dados de cada clínica isolados das outras e proteger dados sensíveis (CPF e notas clínicas) exigidos pela LGPD;
- aguentar centenas de clínicas num banco só (a última contagem nos rastros é de 900 clínicas ativas em 2025-02-03, `contexto/slack/geral.md`) com deploys sem estado misto entre clínicas.

## 2. Arquitetura geral

Monólito Laravel 10 em PHP 8.2 ([ADR-001](adrs/ADR-001-monolito-laravel-com-telas-em-blade.md)), com duas portas de entrada no mesmo código:

- o **painel** web, com telas renderizadas no servidor em Blade e sessão do Laravel;
- a **API REST** `/api/v1` para o app do paciente ([ADR-007](adrs/ADR-007-api-rest-versionada-na-url-no-monolito.md)), com tokens do Sanctum ([ADR-008](adrs/ADR-008-autenticacao-da-api-com-tokens-do-sanctum.md)).

O mesmo código roda em três processos: a aplicação web (PHP-FPM atrás do nginx), o **worker** de filas e o **scheduler**. O estado fica fora das instâncias da aplicação: PostgreSQL para os dados ([ADR-002](adrs/ADR-002-postgresql-como-banco-de-dados.md)), Redis para fila, cache e sessão ([ADR-005](adrs/ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md), [ADR-011](adrs/ADR-011-sessoes-do-painel-no-redis.md)) e S3 para anexos ([ADR-010](adrs/ADR-010-anexos-no-s3-com-url-pre-assinada.md)).

Multi-tenancy: um schema único no PostgreSQL, com coluna `tenant_id` nas tabelas da clínica e escopo global do Eloquent ([ADR-013](adrs/ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)).

```
navegador (equipe) ──HTTPS──┐
                            ├─► balanceador* ─► nginx ─► PHP-FPM (Laravel: painel + /api/v1)
app do paciente ──HTTPS/JSON┘                                 │
                                                              ├─► PostgreSQL (schema único, tenant_id)
scheduler (schedule:work) ─► lembretes:enfileirar ─► Redis ◄──┤   (fila, cache, sessão)
                                                      │       └─► S3 (anexos, URL pré-assinada)
worker (queue:work redis) ◄───────────────────────────┘
   └─► WhatsApp Cloud API (Meta) ──falha/sem aceite──► SMS (Twilio)

* balanceador e duas instâncias da aplicação: produção na AWS, conhecida só pelos rastros
```

**Ambientes.** No desenvolvimento, o `docker-compose.yml` sobe `nginx`, `app`, `worker`, `scheduler`, `postgres` (16), `redis` (7.2) e `s3` (`adobe/s3mock`, que faz o papel do S3). A produção roda na AWS ([ADR-009](adrs/ADR-009-migracao-da-infraestrutura-para-a-aws.md)): RDS (PostgreSQL), ElastiCache (Redis), S3, duas instâncias da aplicação atrás de um balanceador e contêiner de worker (`docs/postmortems/2022-04-12-deploy-travado.md`, `contexto/slack/arquitetura.md`). O repositório não tem infraestrutura como código, então a topologia de produção não pode ser conferida no código.

## 3. Componentes

Todos dentro do container da aplicação Laravel (`app/`).

| Componente | Onde está | Responsabilidade |
|---|---|---|
| Painel web | `app/Http/Controllers/*Controller.php`, `resources/views/`, `routes/web.php` | Agenda do dia, novo agendamento, mudança de status, pacientes (busca por nome e por CPF), profissionais, lista de espera, upload e download de anexos. |
| Login do painel | `app/Http/Controllers/Auth/LoginController.php` | Login por e-mail e senha da equipe (`users`), sessão do Laravel no Redis. |
| API v1 | `app/Http/Controllers/Api/V1/`, `app/Http/Resources/AgendamentoResource.php`, `routes/api.php` | Token do paciente, horários livres, listar, marcar ou remarcar (`reagendar_de`) e cancelar agendamentos. |
| Tenancy | `app/Http/Middleware/IdentificarTenant.php`, `app/Tenancy/TenantContext.php`, `app/Tenancy/TenantScope.php`, `app/Tenancy/BelongsToTenant.php` | Descobre a clínica pelo usuário autenticado (equipe ou paciente dono do token), guarda no contexto da requisição, filtra e preenche `tenant_id` nos models. |
| Agenda / disponibilidade | `app/Agenda/Disponibilidade.php` | Calcula horários livres e conflitos a partir de disponibilidades, bloqueios, feriados (`config/feriados.php`) e agendamentos ativos, no fuso da clínica. Usado pelo painel e pela API. |
| Lembretes | `app/Console/Commands/EnfileirarLembretes.php`, `app/Jobs/EnviarLembreteAgendamento.php`, `app/Lembretes/` | O comando seleciona os agendamentos das próximas 24h de cada clínica e despacha um job por lembrete na fila `notificacoes`. O job monta a mensagem e envia pela cadeia de canais. |
| Canais de lembrete | `app/Lembretes/CanalLembrete.php`, `app/Lembretes/CanalComFallback.php`, `app/Lembretes/FabricaDeCanais.php`, `app/Lembretes/Canais/WhatsAppCloud.php`, `app/Lembretes/Canais/SmsTwilio.php`, `app/Lembretes/Canais/LogCanal.php` | Interface `CanalLembrete` com implementações para WhatsApp (Cloud API da Meta) e SMS (Twilio). `CanalComFallback` tenta os canais em ordem (`config/lembretes.php`: `whatsapp`, `sms`). `LogCanal` substitui o envio real quando o driver é `log` ([ADR-006](adrs/ADR-006-lembretes-por-sms-com-twilio-atras-da-interface-canallembrete.md), [ADR-014](adrs/ADR-014-whatsapp-cloud-api-como-canal-principal-com-fallback-para-sms.md)). |
| Anexos | `app/Anexos/ArmazenamentoAnexos.php`, `app/Http/Controllers/AnexoController.php` | Grava o arquivo no disco `s3` em `tenants/{tenant}/agendamentos/{id}/{uuid}.{ext}` e entrega o download por URL temporária de 10 minutos. |
| Privacidade | casts `encrypted` em `app/Models/Paciente.php` e `app/Models/Agendamento.php`, `app/Criptografia/HashCpf.php`, `app/Rules/Cpf.php` | CPF e notas clínicas criptografados pela aplicação, `cpf_hash` (HMAC-SHA256 com `CPF_HASH_KEY`) para a busca por CPF ([ADR-012](adrs/ADR-012-criptografia-de-campo-para-cpf-e-notas-clinicas.md)). |
| Eventos de agendamento | `app/Events/AgendamentoStatusAlterado.php`, `app/Listeners/RegistrarMudancaDeStatus.php`, `app/Listeners/AvisarListaEspera.php` | Toda mudança de status dispara um evento. Um listener grava a mudança no log e o outro marca como avisadas as entradas da lista de espera quando há cancelamento. Os listeners são síncronos. |
| Comandos de manutenção | `app/Console/Commands/RelatorioFaltas.php`, `app/Console/Commands/CriptografarDadosPacientes.php` | `relatorios:faltas {clinica}` calcula a taxa de faltas por profissional no mês. `pacientes:criptografar` converte dados gravados antes da criptografia por campo. |

## 4. Fluxo de requisição

### 4.1 Painel: criar um agendamento

1. O navegador manda `POST /agendamentos`. O nginx entrega para o PHP-FPM.
2. O grupo `web` carrega a sessão do Redis e confere o CSRF. O middleware `auth` exige login.
3. `IdentificarTenant` busca o `Tenant` do usuário e o guarda no `TenantContext`. Sem tenant, a resposta é 403.
4. `AgendamentoController@store` valida, pede ao serviço `Disponibilidade` os conflitos e os horários livres no fuso da clínica e cria o agendamento. O `BelongsToTenant` preenche o `tenant_id`, e o `TenantScope` filtra todas as consultas Eloquent por ele.
5. A resposta é um redirect para a tela da agenda, renderizada em Blade.

### 4.2 API: marcar pelo app

1. `POST /api/v1/auth/token` com e-mail, senha, slug da clínica e nome do dispositivo. O Sanctum grava o token em `personal_access_tokens` e devolve um bearer.
2. `POST /api/v1/agendamentos` com `Authorization: Bearer`. Passa por `throttle:api` (60/min por usuário ou IP), `auth:sanctum` e `tenant`. A clínica vem do paciente dono do token (`9356b12`).
3. Dentro de uma transação, o controller trava o profissional (`lockForUpdate`), confere conflito (409 se o horário acabou de ser ocupado) e se o horário está livre (422 se não está), e cria o agendamento. Com `reagendar_de`, o agendamento anterior é cancelado na mesma transação.
4. A resposta é 201 com o `AgendamentoResource`.

### 4.3 Lembrete

1. O `scheduler` roda `schedule:work`, que executa `lembretes:enfileirar` a cada 10 minutos com `withoutOverlapping` (`app/Console/Kernel.php`).
2. Para cada clínica, o comando calcula o "agora" no fuso dela (`Tenant::agora()`, desde o hotfix `81c9949`), seleciona os agendamentos `agendado` e `confirmado` das próximas 24 horas sem `lembrete_enviado_em` e despacha `EnviarLembreteAgendamento` na fila `notificacoes`. Um `Cache::add` por agendamento, válido por 1 hora, evita despachar o mesmo lembrete duas vezes.
3. O `worker` (`queue:work redis --queue=notificacoes,default --tries=3 --timeout=60`) executa o job, que tem `timeout` de 30s, 3 tentativas e backoff de 30, 120 e 300 segundos (`83763e2`).
4. O job envia por `CanalComFallback`. Vai por WhatsApp se o paciente tem `aceita_whatsapp` e telefone. Se não tem, ou se o WhatsApp falhar, vai por SMS. Depois grava `lembrete_enviado_em`.

### 4.4 Download de anexo

`GET /anexos/{id}` (com login e tenant). O controller encontra o anexo pelo escopo do tenant e redireciona para uma URL pré-assinada do S3, válida por 10 minutos. O arquivo nunca fica público.

## 5. Modelo de dados

Um banco PostgreSQL com tudo no schema `public`. As tabelas da clínica levam `tenant_id`, com chave estrangeira para `tenants` (`database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php`).

| Tabela | Conteúdo | Observações |
|---|---|---|
| `tenants` | clínica: `nome`, `slug`, `timezone` | A coluna `schema` foi removida em 2022 (`2022_06_28_113000_remove_schema_from_tenants_table.php`). |
| `users` | equipe: `name`, `email`, `password`, `papel` (admin, recepção, profissional), `tenant_id` | Login do painel. |
| `profissionais`, `servicos` | quem atende e o quê (com `duracao_minutos`) | |
| `disponibilidades` | faixa semanal por profissional (`dia_semana`, `hora_inicio`, `hora_fim`) | |
| `bloqueios` | folgas e férias (`data`, `data_fim`, `motivo`) | |
| `pacientes` | `nome`, `cpf` (criptografado), `cpf_hash`, `telefone`, `email`, `data_nascimento`, `senha` (app), `aceita_whatsapp` | |
| `agendamentos` | paciente, profissional, serviço, `inicio`, `fim`, `status` (agendado, confirmado, cancelado, faltou, realizado), `notas_clinicas` (criptografado), `link_teleconsulta`, `convenio`, `lembrete_enviado_em` | O horário é gravado no fuso da clínica (`tenants.timezone`). |
| `anexos` | `agendamento_id`, `caminho` no S3, `nome_original`, `tipo`, `tamanho` | |
| `lista_espera` | paciente, profissional (opcional), serviço, `data_desejada`, `observacao`, `avisado_em` | |
| `personal_access_tokens` | tokens Sanctum (polimórfico, `tokenable` = paciente), `expires_at` | |
| `failed_jobs`, `password_resets` | tabelas de infraestrutura do Laravel | |

## 6. Interfaces públicas

**API REST v1** (`routes/api.php`), JSON, autenticação bearer (Sanctum), limite de 60 req/min:

| Método e rota | Uso |
|---|---|
| `POST /api/v1/auth/token` | login do paciente (`email`, `senha`, `clinica`, `dispositivo`), devolve o token |
| `GET /api/v1/horarios` | horários livres de um profissional e serviço numa data |
| `GET /api/v1/agendamentos` | agendamentos do paciente, paginados (20 por página) |
| `POST /api/v1/agendamentos` | marca ou remarca (`reagendar_de`); 409 em conflito, 422 fora da agenda |
| `DELETE /api/v1/agendamentos/{id}` | cancela; 404 se o agendamento é de outro paciente |

O contrato da v1 não quebra: o app de loja não força atualização, e uma mudança incompatível vira `/api/v2` ([ADR-007](adrs/ADR-007-api-rest-versionada-na-url-no-monolito.md)). Até 2023-08-01 o app 3.0 ainda usava a v1 (`contexto/slack/arquitetura.md`).

**Painel web** (`routes/web.php`): HTML por sessão, só para a equipe da clínica. `GET /health` devolve `{"status":"ok"}` para o balanceador.

**Integrações de saída:** WhatsApp Cloud API (`graph.facebook.com/{versão}/{phone_number_id}/messages`, template `lembrete_consulta`), Twilio (SMS), S3 (anexos) e Sentry (erros).

## 7. Escalabilidade

- **Aplicação sem estado local:** sessão no Redis e anexos no S3 deixam rodar várias instâncias atrás do balanceador ([ADR-010](adrs/ADR-010-anexos-no-s3-com-url-pre-assinada.md), [ADR-011](adrs/ADR-011-sessoes-do-painel-no-redis.md)). O `GET /health` (`3f8c39d`) serve de verificação de saúde. O `TrustProxies` chegou a confiar em qualquer proxy (`69b2887`, preparação para o balanceador), mas no HEAD está de novo sem proxies confiáveis (veja o risco 12).
- **Trabalho assíncrono:** o envio de lembretes fica fora da requisição e do comando agendado. O comando só enfileira, e o worker escala separado ([ADR-005](adrs/ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md)).
- **Banco único:** a migration roda uma vez para todas as clínicas, e o número de conexões não cresce com o número de clínicas ([ADR-013](adrs/ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)). O índice em `tenant_id` sustenta o filtro por clínica.
- **Limites conhecidos:** um PostgreSQL só (RDS, uma instância segundo o postmortem), um scheduler e listeners síncronos. A busca de pacientes por nome roda no banco. Em 2023 o time testou o Meilisearch e concluiu que, para o volume da época (a maior clínica tinha cerca de 30 mil pacientes), o banco dava conta (veja os candidatos descartados no [índice](adrs/README.md)).

## 8. Segurança

- **Isolamento entre clínicas:** `tenant_id` com escopo global, testes de isolamento (`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`) e revisão de `withoutGlobalScopes` e `DB::table` ([ADR-013](adrs/ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)).
- **Dados sensíveis:** CPF e notas clínicas com o cast `encrypted` (chave `APP_KEY`) e busca por `cpf_hash` com chave própria ([ADR-012](adrs/ADR-012-criptografia-de-campo-para-cpf-e-notas-clinicas.md)). Nos rastros, o banco gerenciado também tem criptografia de disco (e-mail da DPO, `contexto/emails/2022-05-03-dpo-criptografia.md`).
- **Autenticação:** sessão para a equipe e token opaco do Sanctum por dispositivo para o paciente. Revogar um token é apagar a linha ([ADR-008](adrs/ADR-008-autenticacao-da-api-com-tokens-do-sanctum.md)). Senhas com hash (`hashed`).
- **Anexos:** bucket privado e URL pré-assinada de 10 minutos ([ADR-010](adrs/ADR-010-anexos-no-s3-com-url-pre-assinada.md)). Nos rastros, o bucket tem bloqueio de acesso público, criptografia e uma role só com put/get.
- **Proteções do framework:** CSRF no painel, `throttle:api` na API e validação de CPF (`app/Rules/Cpf.php`).

## 9. Observabilidade

- **Erros:** Sentry (`sentry/sentry-laravel` 4, `config/sentry.php`, `SENTRY_LARAVEL_DSN`). Tracing desligado no exemplo (`SENTRY_TRACES_SAMPLE_RATE=0`). Nos rastros aparece um alerta para mais de 500 eventos iguais em 5 minutos (ação do postmortem).
- **Logs:** `LOG_CHANNEL=stderr` no `.env.example` (logs no stdout/stderr do contêiner). O `RegistrarMudancaDeStatus` registra toda mudança de status de agendamento, e o `CanalComFallback` registra cada falha de canal.
- **Filas:** os jobs que falham vão para `failed_jobs` e podem ser reprocessados com `queue:retry`.
- **Saúde:** `GET /health` e healthchecks dos contêineres no compose.
- **Não existem no código:** métricas de aplicação, tracing distribuído e painel de filas.

## 10. Riscos

Riscos e problemas que enxerguei no código e nas fontes. Nenhum deles foi corrigido, porque está fora do escopo.

1. **Escopo de tenant desligado fora de requisição.** O `TenantScope` só filtra quando há tenant no contexto. No worker, no scheduler e nos comandos não há contexto, então as consultas Eloquent enxergam todas as clínicas. Hoje o código filtra `tenant_id` na mão onde importa (`EnfileirarLembretes`, `AvisarListaEspera`). A ata de 2022-05-20 previa que "os jobs carregam o `tenant_id` no payload", mas o `EnviarLembreteAgendamento` só leva o `agendamentoId`. Um job ou comando novo que esqueça o filtro mistura clínicas.
2. **Papéis sem autorização.** A coluna `users.papel` existe, mas nenhuma rota ou controller confere papel. Recepção e profissional têm o mesmo acesso que o admin.
3. **Lista de espera não avisa ninguém.** O `AvisarListaEspera` só marca `avisado_em` e escreve no log. Nenhuma mensagem chega ao paciente.
4. **Tokens do app sem expiração.** `config/sanctum.php` tem `expiration => null`. A coluna `expires_at` existe, mas o login não define validade.
5. **Chaves de criptografia.** A perda ou o vazamento de `APP_KEY` (cast `encrypted`) ou de `CPF_HASH_KEY` afeta todos os pacientes de todas as clínicas. O repositório não registra como as chaves são guardadas e rotacionadas. O `.env.example` traz uma `CPF_HASH_KEY` fixa de desenvolvimento.
6. **Ponto único no banco e no scheduler.** Um RDS de uma instância (postmortem) e um scheduler só. Se o scheduler parar, os lembretes param sem alerta.
7. **Listeners síncronos.** Os listeners de `AgendamentoStatusAlterado` rodam dentro da requisição. Um listener lento ou com falha afeta a marcação e o cancelamento.
8. **Fallback de lembrete pode duplicar.** Se o WhatsApp entregar e mesmo assim lançar erro (por exemplo, timeout de 10s depois do envio), o paciente recebe também o SMS. Uma nova tentativa do job pode repetir o envio. Em 2024-03-12 já houve SMS repetido por timeout (`contexto/slack/arquitetura.md`).
9. **Documentação de arquitetura enganosa.** O `docs/ARQUITETURA.md` (2019) descreve schema por clínica, lembrete síncrono por e-mail, anexos em disco local, sessão em arquivo, VPS, mailhog e `scripts/deploy.sh`. Ele também planeja GraphQL e um microsserviço de agenda que nunca existiram. Um agente de IA que leia esse arquivo como verdade vai propor coisas que já foram desfeitas.
10. **Narrativa errada sobre a unificação do banco.** Em `#geral` (2023-10-17), a unificação aparece como medida de economia no RDS, o que contradiz a ata que a decidiu. Isso pode levar a decisões futuras com a premissa errada (veja a [ADR-013](adrs/ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)).
11. **Produção fora do repositório.** Não há infraestrutura como código nem pipeline versionada. A topologia de produção só é conhecida pelos rastros.
12. **Proxies confiáveis perdidos no upgrade.** Em `69b2887` (2021-05-11), `app/Http/Middleware/TrustProxies.php` passou a ter `$proxies = '*'` para rodar atrás do balanceador. O upgrade para Laravel 10 (`40d1dc9`) voltou a propriedade para vazia, sem explicação no commit. Se a produção não define isso de outra forma, IP do cliente, esquema HTTPS e URLs geradas podem sair errados atrás do balanceador, e o `throttle:api` por IP passa a contar o IP do balanceador.
13. **Seed a cada subida.** O `docker/entrypoint.sh` roda `migrate --force` e `db:seed --force` sempre que o `php-fpm` sobe. Isso é adequado ao desenvolvimento. O repositório não mostra se a mesma imagem e o mesmo entrypoint são usados em produção.

## 11. O que deixou de existir (para não confundir)

Nada disto existe no HEAD: schema por clínica (`DefinirSchemaTenant`, `tenants:migrate`, `tenants:criar`), envio de lembrete síncrono (`lembretes:enviar`), canal de e-mail dos lembretes (`EmailCanal`, removido em `5b4aca3`), anexos em disco local, sessão em arquivo, VPS e `scripts/deploy.sh`, mailhog, minio (trocado por `adobe/s3mock` no compose), Scout e Meilisearch (revertidos em 2023), o cabeçalho `X-Clinica` na API (`9356b12`) e o php-cs-fixer (trocado pelo Pint).
