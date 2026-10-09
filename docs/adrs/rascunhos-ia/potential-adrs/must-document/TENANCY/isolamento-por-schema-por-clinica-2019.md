# ADR Potencial: Isolamento por schema PostgreSQL por clínica (decisão de 2019, substituída)

**Módulo**: TENANCY
**Categoria**: Arquitetura / Segurança (decisão histórica, substituída)
**Prioridade**: Must Document (Score: 145)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe. Esta decisão foi **substituída** por [schema-unico-com-tenant-id-e-escopo-global.md](./schema-unico-com-tenant-id-e-escopo-global.md). Sugestão: gerar o ADR com status "Substituída por ADR-XXX", preservando o raciocínio original. Hoje só resta rastro em `docs/ARQUITETURA.md` (desatualizado) e no histórico git; o código já não contém este modelo.

---

## O Que Foi Identificado

Na reunião de kickoff de 2019-04-02 (`contexto/atas/2019-04-02-kickoff-tecnico.md`) foi decidido isolar os dados de cada clínica em um **schema PostgreSQL próprio** (`clinica_<slug>`), com as mesmas tabelas em cada um. A orientação veio do advogado da empresa (Dr. Otávio): por tratar dados de saúde (sensíveis na LGPD), o isolamento deveria ser "o mais forte viável", para que um erro de programação não expusesse pacientes de uma clínica a outra. A aplicação identificava a clínica pelo usuário logado e apontava o schema certo a cada requisição.

Alternativa descartada na ata: banco único com coluna `clinica_id` e filtro nas consultas, pelo risco de uma consulta sem filtro vazar dados. Ironicamente é o modelo adotado em 2022. Rafael já antecipou na ata a necessidade de um jeito de migrar todos os schemas e de criar o schema de clínica nova.

Implementação no git: `3b304c1` (2019-05-06, "wip tenancy": `Tenant`, `GerenciadorSchemas`, `tenants` e `users.tenant_id`), `7a4da6a` (2019-05-08: migrations da clínica em `database/migrations/tenant` e middleware `DefinirSchemaTenant` trocando o `search_path`), `797ec44` (2019-05-10, `tenants:migrate`), `a9fcba1` (2019-05-14, ajustes da agenda), `47031f4` (2019-05-17, `tenants:criar`). Evolução do `tenants:migrate`: progresso (`6d40f5d`, 2019-11), continuar se um schema falhar (`827eee1`, 2020-12), log do tempo por schema (`d2bbdab`, 2022-04-20, ação do postmortem). A API (2021) estendeu o modelo com o header `X-Clinica` (ver [identificacao-do-tenant-por-requisicao.md](./identificacao-do-tenant-por-requisicao.md)).

## Por Que Isto Merece um ADR

- **Impacto**: moldou 3 anos do sistema (2019-05 a 2022-06): migrations, jobs de lembrete (que trocavam de schema por clínica), API, deploy.
- **Trade-offs**: ganho: isolamento físico (LGPD). Custo que se revelou: tempo de migration linear no número de clínicas (10 min com ~60 clínicas em 2020; 5h30 com 380 em 2022) e deploy com estado misto. Ninguém percebeu a curva (postmortem).
- **Complexidade**: média (troca de `search_path` com `DB::purge`/`reconnect` a cada uso, pasta de migrations separada).
- **Conhecimento**: a regra "migration de clínica vai em `database/migrations/tenant`" era armadilha conhecida (`docs/ARQUITETURA.md`).
- **Valor da decisão**: é a explicação do porquê de existirem a trait, o Postmortem e a condição de criptografia hoje; sem ela, a decisão de 2022 parece arbitrária.
- **Temporal**: vigente 2019-05 a 2022-06-28 (~3 anos e 1 mês).

## Evidências Encontradas

Código atual: **não existe mais** (removido em `827b1b7`: `GerenciadorSchemas`, `DefinirSchemaTenant`, `TenantsMigrate`, `CriarTenant`). Evidências:
- [`docs/ARQUITETURA.md`](../../../../ARQUITETURA.md) - seção "Banco de dados e clínicas" (julho de 2019).
- [`contexto/atas/2019-04-02-kickoff-tecnico.md`](../../../../../contexto/atas/2019-04-02-kickoff-tecnico.md) - seção 3.
- [`docs/postmortems/2022-04-12-deploy-travado.md`](../../../../postmortems/2022-04-12-deploy-travado.md).
- Slack #arquitetura 2019-05-14, 2020-02-12 (Rafael rejeita a fila `database` também por "dúvida de em qual schema essa tabela ficaria") e 2021-02-18.
- Commits: `3b304c1`, `7a4da6a`, `797ec44`, `47031f4`, `d2bbdab`, `827b1b7`.

### Análise de Impacto
- Introduzido: 2019-05-06 (decisão em 2019-04-02).
- Removido: 2022-06-28 (`827b1b7`), cópia de dados em `f60eb60`.
- Efeito colateral visível: jobs de lembrete carregavam `tenantId` e trocavam o schema no `handle` (antes de `827b1b7`).

## Perguntas a Responder no ADR

- O advogado/escritório recomendou por exigência legal ou por prudência? (A ata fala em "recomendou", não em obrigação.)
- Por que a escalabilidade (clínicas por schema) não foi discutida em 2019? (Provavelmente porque eram 3 clínicas.)
- Alternativa "banco por clínica" foi considerada em 2019? (Não aparece na ata.)

## ADRs Potenciais Relacionados
- [schema-unico-com-tenant-id-e-escopo-global.md](./schema-unico-com-tenant-id-e-escopo-global.md) (a substitui)
- [identificacao-do-tenant-por-requisicao.md](./identificacao-do-tenant-por-requisicao.md)

## Notas Adicionais

O postmortem de 2022-04-12 diz que em 2020, com ~60 clínicas, migration parecida levava ~10 minutos. Não há commit que confirme esse número; vem só do texto do postmortem.
