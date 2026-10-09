# ADR-002: PostgreSQL como banco de dados

- **Status:** Accepted
- **Data:** 2019-04-02
- **Decisores:** Rafael Lima (CTO)
- **Relações:** nenhuma relação de substituição, emenda ou dependência. A [ADR-003](ADR-003-um-schema-por-clinica-no-banco.md) depende desta.

## Contexto e problema

O sistema precisava de um banco relacional para agenda, pacientes e clínicas. A ata do kickoff registra o banco só em "Outros pontos de stack": "Banco: Postgres (o Rafael já deixou configurado)" (`contexto/atas/2019-04-02-kickoff-tecnico.md`). O projeto inicial, do dia anterior, já vinha com `DB_CONNECTION=pgsql` e um serviço `postgres` no `docker-compose.yml` (`c5c258b`, 2019-04-01).

## Opções consideradas

- **PostgreSQL**, a escolhida.
- **Needs Input:** nenhuma fonte registra outras opções avaliadas (MySQL, que é o padrão mais comum com Laravel, por exemplo) nem se houve avaliação.

## Decisão

Usar PostgreSQL como banco único da aplicação.

**Needs Input:** as fontes não dizem por que o PostgreSQL foi escolhido. A ata só registra que ele já estava configurado pelo Rafael, e o Rafael saiu da empresa em 2024-06-28. Falta saber o motivo da escolha e quais alternativas, se alguma, foram consideradas.

O que as fontes mostram é que recursos específicos do PostgreSQL passaram a sustentar decisões seguintes:

- o isolamento por schema com `search_path` ([ADR-003](ADR-003-um-schema-por-clinica-no-banco.md), `7a4da6a`);
- o Row Level Security, avaliado e descartado em 2022 ([ADR-013](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)).

Não há evidência de que esses recursos tenham motivado a escolha em 2019. É só uma consequência observada.

A decisão segue vigente: o banco foi para o RDS na AWS ([ADR-009](ADR-009-migracao-da-infraestrutura-para-a-aws.md)) e teve upgrades de versão (13 em `ea99585`, 15 em `3962b4f`, 16 em `c83bd4a`).

## Consequências

### Positivas

- O isolamento por schema da [ADR-003](ADR-003-um-schema-por-clinica-no-banco.md) só foi possível com o suporte a múltiplos schemas e `search_path` do PostgreSQL.
- Disponível como serviço gerenciado (RDS), o que permitiu a migração para a AWS sem trocar de banco.

### Negativas

- **Needs Input:** as fontes não registram trade-offs da escolha em relação a outras opções.
- O banco é ponto único: em 2022 o postmortem descreve uma instância RDS só, e o limite de conexões do RDS aparece como preocupação na ata de 2022-05-20.

## Evidências

- Commits: `c5c258b` (projeto inicial com `DB_CONNECTION=pgsql` e serviço `postgres` no compose), `ea99585` (PostgreSQL 13), `3962b4f` (PostgreSQL 15), `c83bd4a` (PostgreSQL 16).
- Arquivos: `config/database.php`, `.env.example`, `docker-compose.yml`.
- Rastros: `contexto/atas/2019-04-02-kickoff-tecnico.md` (seção 2, "Outros pontos de stack"), `docs/postmortems/2022-04-12-deploy-travado.md` (contexto da infraestrutura), `contexto/atas/2022-05-20-reuniao-tenancy.md` (opção b, limite de conexões).
