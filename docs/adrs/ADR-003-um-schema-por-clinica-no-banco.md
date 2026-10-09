# ADR-003: Um schema por clínica no banco

- **Status:** Superseded
- **Data:** 2019-04-02
- **Decisores:** Helena Duarte (CEO), Rafael Lima (CTO), Juliana Prado (desenvolvedora), por orientação do advogado da empresa
- **Relações:**
  - superseded by [ADR-013: Schema único com tenant_id e escopo global](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)
  - depends on [ADR-002: PostgreSQL como banco de dados](ADR-002-postgresql-como-banco-de-dados.md)

## Contexto e problema

A Horalis ia guardar dados de saúde de pacientes de várias clínicas, que a LGPD trata como dados sensíveis. O advogado da empresa recomendou que os dados de cada clínica ficassem isolados uns dos outros "da forma mais forte que for viável, para que um erro de programação não exponha pacientes de uma clínica para outra" (ata do kickoff, seção 3).

A pergunta era como separar os dados das clínicas no banco.

## Opções consideradas

1. **Um schema por clínica** (`clinica_<slug>`) no mesmo banco PostgreSQL, com as mesmas tabelas em cada schema.
2. **Banco único com uma coluna `clinica_id`** em cada tabela, filtrando por ela nas consultas.

## Decisão

Opção 1. Cada clínica ganha seu próprio schema `clinica_<slug>`. A aplicação identifica a clínica pelo usuário logado e aponta para o schema certo em cada requisição. O schema `public` guarda só `tenants` e `users` (`docs/ARQUITETURA.md`).

A opção 2 foi descartada pelo risco de vazamento: "basta uma consulta sem o filtro para mostrar dados de outra clínica. Com schemas separados, uma consulta esquecida não enxerga outra clínica" (ata do kickoff).

Rafael registrou o custo conhecido da escolha: seria preciso rodar as migrations em todos os schemas e criar o schema de cada clínica nova.

Implementação:

- `3b304c1` (wip): tabela `tenants`, `GerenciadorSchemas`.
- `7a4da6a`: migrations da clínica em `database/migrations/tenant` e middleware `DefinirSchemaTenant`, que troca o `search_path`.
- `797ec44`: comando `tenants:migrate`.
- `47031f4`: comando `tenants:criar`.

O Slack confirma o funcionamento em 2019-05-14.

## Consequências

### Positivas

- Uma consulta sem filtro não alcança os dados de outra clínica, que era o objetivo da orientação jurídica.
- A orientação do advogado foi atendida com uma barreira no banco, não só no código da aplicação.

### Negativas

- O tempo de migration cresce com o número de clínicas. Em 2020, com cerca de 60 clínicas, uma migration parecida levava uns 10 minutos. Em 2022-04-12, com 380 schemas, o `tenants:migrate` levou cerca de 5h30, e as clínicas ainda não migradas ficaram com erro 500 durante o deploy (`docs/postmortems/2022-04-12-deploy-travado.md`).
- Durante o deploy existe um estado misto, com parte das clínicas no schema novo e parte no antigo.
- Toda migration da clínica tem que ir para `database/migrations/tenant`. Se for para a pasta normal, a tabela é criada só no `public` e a tela quebra (`docs/ARQUITETURA.md`).
- Precisou de manutenção própria ao longo dos anos: progresso (`6d40f5d`), seguir quando um schema falha (`827eee1`) e log do tempo de cada schema (`d2bbdab`).
- Toda decisão posterior teve que responder "em qual schema isso fica": a fila em 2020 (`contexto/slack/arquitetura.md`, 2020-02-12) e os tokens da API em 2021 (2021-02-18).

## Substituição

Substituída pela [ADR-013](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md), decidida na reunião de 2022-05-20 em resposta ao postmortem. O código do schema por clínica foi removido em `827b1b7` (2022-06-28), e a coluna `tenants.schema` saiu na migration `2022_06_28_113000_remove_schema_from_tenants_table.php`.

## Evidências

- Commits: `3b304c1`, `7a4da6a`, `797ec44`, `47031f4`, `6d40f5d`, `827eee1`, `d2bbdab`, `827b1b7` (remoção).
- Arquivos: `app/Http/Middleware/DefinirSchemaTenant.php` e `app/Console/Commands/TenantsMigrate.php` (existem no histórico até `827b1b7`), `database/migrations/2019_05_06_140000_create_tenants_table.php`.
- Rastros: `contexto/atas/2019-04-02-kickoff-tecnico.md` (seção 3), `docs/ARQUITETURA.md` (seção "Banco de dados e clínicas"), `contexto/slack/arquitetura.md` (2019-05-14), `docs/postmortems/2022-04-12-deploy-travado.md`.
