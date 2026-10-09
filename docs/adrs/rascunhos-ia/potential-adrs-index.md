# Índice de ADRs Potenciais

## Progresso da análise

### Módulos analisados
- **ANEXOS**: 2 de alta prioridade, 4 de média prioridade
- **API**: 4 de alta prioridade, 0 de média prioridade
- **AUTH**: 3 de alta prioridade, 3 de média prioridade
- **DATA**: 5 de alta prioridade, 1 de média prioridade
- **EVENTOS**: 0 de alta prioridade, 0 de média prioridade
- **INFRA**: 6 de alta prioridade, 1 de média prioridade
- **LEMBRETES**: 3 de alta prioridade, 3 de média prioridade
- **PRIVACIDADE**: 3 de alta prioridade, 3 de média prioridade
- **TENANCY**: 3 de alta prioridade, 2 de média prioridade

### Análise pendente
PAINEL, AGENDA (sem arquivos até agora)

## Alta prioridade (must-document/)

### Módulo: ANEXOS

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Armazenar anexos de agendamento em S3 (bucket privado) | 145 | Tecnologia / Infraestrutura | [link](./potential-adrs/must-document/ANEXOS/armazenamento-de-anexos-em-s3.md) |
| Entregar anexos por redirecionamento para URL pré-assinada de 10 minutos | 120 | Segurança / Arquitetura | [link](./potential-adrs/must-document/ANEXOS/download-por-url-temporaria-pre-assinada.md) |

### Módulo: API

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| API embutida no monólito Laravel, em vez de GraphQL e serviço separado (plano de 2019 abandonado) | 130 | Arquitetura (decisão superada/revertida) | [link](./potential-adrs/must-document/API/api-embutida-no-monolito-em-vez-de-graphql-e-servico-separado.md) |
| API REST versionada na URL (`/api/v1`) para o app do paciente | 145 | Arquitetura (protocolo/estilo de API) | [link](./potential-adrs/must-document/API/api-rest-versionada-na-url-no-monolito.md) |
| Como a API identifica a clínica (header `X-Clinica` em 2021, dono do token desde 2022) | 115 | Segurança / Arquitetura (decisão superada) | [link](./potential-adrs/must-document/API/identificacao-do-tenant-na-api-do-cabecalho-para-o-dono-do-token.md) |
| Autenticação da API com Sanctum (token opaco por dispositivo) em vez de JWT | 120 | Segurança | [link](./potential-adrs/must-document/API/sanctum-token-opaco-por-dispositivo-em-vez-de-jwt.md) |

### Módulo: AUTH

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Autenticação por sessão (e-mail e senha) para a equipe no painel | 130 | Segurança / Arquitetura | [link](./potential-adrs/must-document/AUTH/autenticacao-por-sessao-no-painel.md) |
| Identificação da clínica na API pelo dono do token (substitui o cabeçalho X-Clinica) | 115 | Segurança / Arquitetura | [link](./potential-adrs/must-document/AUTH/identificacao-do-tenant-pelo-dono-do-token.md) |
| Laravel Sanctum (token opaco por dispositivo) em vez de JWT para a API do paciente | 130 | Segurança / Tecnologia | [link](./potential-adrs/must-document/AUTH/tokens-sanctum-por-dispositivo-em-vez-de-jwt.md) |

### Módulo: DATA

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Busca de pacientes mantida no PostgreSQL (POC de Laravel Scout + Meilisearch revertida) | 105 | Tecnologia / Decisão descartada | [link](./potential-adrs/must-document/DATA/busca-de-pacientes-no-postgres-meilisearch-revertido.md) |
| Eloquent (Active Record) como camada de acesso a dados e migrations do Laravel | 145 | Arquitetura / Tecnologia (Step 0, Categoria 3: ORM) | [link](./potential-adrs/must-document/DATA/eloquent-orm-e-migrations-laravel.md) |
| PostgreSQL como banco de dados relacional único | 150 | Tecnologia (Step 0, Categoria 1: serviço de infraestrutura) | [link](./potential-adrs/must-document/DATA/postgresql-como-banco-relacional.md) |
| Um schema PostgreSQL por clínica | 140 | Arquitetura / Dados / Segurança | [link](./potential-adrs/must-document/DATA/schema-por-clinica-no-postgres-2019-substituida.md) |
| Schema único com coluna tenant_id e escopo global (substitui o schema por clínica) | 145 | Arquitetura / Dados | [link](./potential-adrs/must-document/DATA/schema-unico-com-tenant-id-substitui-schema-por-clinica.md) |

### Módulo: INFRA

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Aplicação stateless com múltiplas instâncias atrás de balanceador (sessão no Redis, arquivos no S3, logs em stderr, health check) | 120 | Arquitetura | [link](./potential-adrs/must-document/INFRA/aplicacao-stateless-multi-instancia-atras-de-balanceador.md) |
| Hospedagem na AWS e processo de deploy (substituiu a VPS única com script SSH e cron) | 140 | Arquitetura / Infraestrutura | [link](./potential-adrs/done/INFRA/aws-como-hospedagem-e-pipeline-de-deploy.md) |
| Empacotamento em contêineres Docker (nginx + PHP-FPM, Compose, entrypoint com migrate/seed, imagens com tag fixa) | 110 | Infraestrutura / Plataforma | [link](./potential-adrs/must-document/INFRA/conteineres-docker-compose-nginx-php-fpm.md) |
| Busca de pacientes com Meilisearch e Laravel Scout (POC revertida) | 100 | Tecnologia (decisão descartada) | [link](./potential-adrs/must-document/INFRA/meilisearch-e-scout-poc-revertida.md) |
| Redis como fila, cache e sessão (e descarte de fila em banco e SQS) | 150 | Tecnologia | [link](./potential-adrs/must-document/INFRA/redis-fila-cache-e-sessao.md) |
| Worker de fila e scheduler como processos dedicados (substituindo cron e supervisor da VPS) | 115 | Arquitetura | [link](./potential-adrs/must-document/INFRA/worker-e-scheduler-em-processos-dedicados.md) |

### Módulo: LEMBRETES

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Envio de lembretes assíncrono com fila Redis e worker dedicado | 145 | Arquitetura / Infraestrutura | [link](./potential-adrs/must-document/LEMBRETES/fila-redis-worker-dedicado-para-lembretes.md) |
| Interface CanalLembrete, cadeia de fallback entre canais e canal único global por .env | 120 | Arquitetura / Padrão de projeto | [link](./potential-adrs/must-document/LEMBRETES/interface-canallembrete-com-cadeia-de-fallback.md) |
| WhatsApp Cloud API (Meta) direta como canal principal de lembretes, com opt-in | 125 | Tecnologia / Integração externa | [link](./potential-adrs/must-document/LEMBRETES/whatsapp-cloud-api-direta-como-canal-principal.md) |

### Módulo: PRIVACIDADE

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Índice cego `cpf_hash` (HMAC-SHA256 com chave separada) para busca por CPF | 120 | Segurança / Arquitetura de dados | [link](./potential-adrs/must-document/PRIVACIDADE/busca-por-cpf-com-hash-hmac.md) |
| Criptografia de campo como contrapartida e pré-condição ao fim do isolamento físico por clínica | 110 | Segurança / Conformidade (LGPD) | [link](./potential-adrs/must-document/PRIVACIDADE/criptografia-como-condicao-da-unificacao-do-banco.md) |
| Criptografia em nível de campo (cast `encrypted`) para CPF e notas clínicas | 135 | Segurança | [link](./potential-adrs/must-document/PRIVACIDADE/criptografia-de-campo-cpf-e-notas-clinicas.md) |

### Módulo: TENANCY

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Como o tenant é identificado em cada requisição (usuário/token, não header) | 120 | Arquitetura / Segurança | [link](./potential-adrs/must-document/TENANCY/identificacao-do-tenant-por-requisicao.md) |
| Isolamento por schema PostgreSQL por clínica (decisão de 2019, substituída) | 145 | Arquitetura / Segurança (decisão histórica, substituída) | [link](./potential-adrs/must-document/TENANCY/isolamento-por-schema-por-clinica-2019.md) |
| Multi-tenancy com schema único, `tenant_id` e escopo global na aplicação | 150 | Arquitetura / Segurança (isolamento de dados) | [link](./potential-adrs/must-document/TENANCY/schema-unico-com-tenant-id-e-escopo-global.md) |

## Média prioridade (consider/)

### Módulo: ANEXOS

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Anexos em disco local do servidor (decisão de 2019, substituída por S3) | 85 | Arquitetura (decisão substituída) | [link](./potential-adrs/consider/ANEXOS/anexos-em-disco-local-substituido-por-s3.md) |
| Chave de objeto `tenants/{tenant_id}/agendamentos/{id}/{uuid}.ext` | 80 | Arquitetura / Segurança | [link](./potential-adrs/consider/ANEXOS/chaves-de-objeto-por-tenant-e-uuid.md) |
| Emulador de S3 no ambiente local (MinIO substituído por Adobe S3Mock) | 76 | Tecnologia / Desenvolvimento | [link](./potential-adrs/consider/ANEXOS/s3-local-de-minio-para-s3mock.md) |
| Política de validação de upload (10 MB, PDF/JPG/PNG/TXT, sem varredura) | 76 | Segurança | [link](./potential-adrs/consider/ANEXOS/validacao-de-upload-de-anexos.md) |

### Módulo: AUTH

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Paciente como segundo tipo de identidade, com senha própria cadastrada pela recepção | 95 | Segurança / Arquitetura | [link](./potential-adrs/consider/AUTH/credenciais-proprias-do-paciente-para-o-app.md) |
| Papéis da equipe (admin, recepção, profissional) sem autorização aplicada | 95 | Segurança | [link](./potential-adrs/consider/AUTH/papeis-da-equipe-sem-autorizacao-aplicada.md) |
| Sessão do painel armazenada no Redis (fim da sessão em arquivo) | 95 | Arquitetura / Infraestrutura | [link](./potential-adrs/consider/AUTH/sessao-armazenada-no-redis.md) |

### Módulo: DATA

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Horários gravados no fuso local, sem fuso, no banco (e fuso por clínica) | 75 | Arquitetura / Dados | [link](./potential-adrs/consider/DATA/horarios-locais-sem-fuso-no-banco-e-fuso-por-clinica.md) |

### Módulo: INFRA

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Observabilidade com Sentry e logs em stderr | 90 | Observabilidade | [link](./potential-adrs/consider/INFRA/sentry-e-logs-em-stderr.md) |

### Módulo: LEMBRETES

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Política de idempotência e retry do envio de lembretes | 80 | Confiabilidade | [link](./potential-adrs/consider/LEMBRETES/politica-de-idempotencia-e-retry-do-envio-de-lembretes.md) |
| Seleção de lembretes por polling a cada 10 min, janela de 24 h e "agora" no fuso da clínica | 76 | Arquitetura / Decisão corrigida por incidente | [link](./potential-adrs/consider/LEMBRETES/selecao-por-polling-a-cada-10-min-com-fuso-da-clinica.md) |
| SMS via Twilio (2020) e aposentadoria do canal de e-mail (2023) | 85 | Tecnologia / Decisão histórica (parcialmente superada) | [link](./potential-adrs/consider/LEMBRETES/sms-twilio-como-canal-e-fim-do-email-como-canal-unico.md) |

### Módulo: PRIVACIDADE

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Conversão in-place e idempotente dos dados existentes (`pacientes:criptografar`) | 80 | Migração de dados / Segurança | [link](./potential-adrs/consider/PRIVACIDADE/conversao-dos-dados-existentes-pacientes-criptografar.md) |
| Escopo dos dados protegidos (apenas CPF e notas clínicas) e dados pessoais mantidos em claro | 78 | Segurança / Conformidade | [link](./potential-adrs/consider/PRIVACIDADE/escopo-dos-campos-protegidos-e-dados-em-claro.md) |
| Gestão das chaves de criptografia (`APP_KEY` e `CPF_HASH_KEY`) | 85 | Segurança | [link](./potential-adrs/consider/PRIVACIDADE/gestao-de-chaves-app-key-e-cpf-hash-key.md) |

### Módulo: TENANCY

| Título | Pontuação | Categoria | Arquivo |
|---|---|---|---|
| Escopo de tenant "aberto por padrão" quando não há contexto (jobs e comandos) | 85 | Segurança / Arquitetura | [link](./potential-adrs/consider/TENANCY/escopo-aberto-sem-contexto-ativo-em-jobs-e-comandos.md) |
| Fuso horário por clínica e "agora" calculado na aplicação (não no banco) | 75 | Arquitetura / Confiabilidade | [link](./potential-adrs/consider/TENANCY/fuso-horario-por-clinica-e-calculo-de-agora-na-aplicacao.md) |

## Observações de DATA e EVENTOS (2026-10-09)

- **Duplicidades entre módulos a consolidar antes da Fase 3**: DATA/`schema-unico-...` e DATA/`schema-por-clinica-...` com TENANCY/`schema-unico-com-tenant-id-e-escopo-global` e TENANCY/`isolamento-por-schema-por-clinica-2019` (e os ADR-003/ADR-013); DATA/`busca-de-pacientes-...` com INFRA/`meilisearch-e-scout-poc-revertida`; DATA/`horarios-locais-...` com TENANCY/`fuso-horario-...` e LEMBRETES/`selecao-por-polling-...`; DATA/`postgresql-...` com o ADR-002 já gerado.
- **EVENTOS**: nenhum ADR potencial atingiu o limiar de 75. Candidatos avaliados e descartados:

| Candidato | Motivo |
|---|---|
| Evento `AgendamentoStatusAlterado` disparado por `Agendamento::alterarStatus`, listeners síncronos (`8aad4ed`, 2022-10-04; `9c5c3ab`, 2022-10-06) | Passa nos 3 E's, mas pontua 45 (escopo 15, custo 15, conhecimento 15): um evento, 2 listeners, sem fonte de contexto sobre o motivo. Candidato mais próximo do limiar; promover se o time quiser registrar a convenção de que toda mudança de status deve passar por `alterarStatus` (`update(['status'=>...])` não dispara o evento). |
| Aviso à lista de espera por listener (`3f24942`, 2023-11-14) | Regra de negócio (Red Flag 2). O listener só marca `avisado_em` e grava log, sem enviar ao paciente: lacuna funcional a confirmar com o time, não decisão documentada. |
| Listeners sem `ShouldQueue`, registro explícito no `EventServiceProvider` | Detalhe do primeiro item (Red Flag 5). |

- **DATA descartados/consolidados**: criptografia de campo e `cpf_hash` (módulo PRIVACIDADE); política de deploy com migration (pontua 50, prática operacional do postmortem); cópia de dados dos schemas (`f60eb60`) e remoção de comandos executados (`d7a7363`) como parte do ADR do schema único; banco `horalis_test`, seeders, upgrades de versão e entrypoint (`eff7ee9`) como configuração; entidades e status do agendamento como modelagem de domínio (Red Flag 1).

## Resumo
- Alta prioridade: 29 ADRs
- Média prioridade: 17 ADRs
- Total: 46 ADRs
- Nota: índice reconstruído em 2026-10-09 a partir dos arquivos existentes, porque execuções paralelas sobrescreveram a versão anterior.
