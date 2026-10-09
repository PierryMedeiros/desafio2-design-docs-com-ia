# ADR Potencial: PostgreSQL como banco de dados relacional único

**Módulo**: DATA
**Categoria**: Tecnologia (Step 0, Categoria 1: serviço de infraestrutura)
**Prioridade**: Documentar obrigatoriamente (Pontuação: 150 = 75 base + 25 escopo + 25 custo de mudança + 25 conhecimento do time)
**Data da identificação**: 2026-10-09

---

## Contexto de ADRs existentes

**DECISÃO SEMELHANTE JÁ FORMALIZADA** (detectado em 2026-10-09, após a identificação):
- **ADR-002**: PostgreSQL como banco de dados (`docs/adrs/ADR-002-postgresql-como-banco-de-dados.md`, Status: Accepted, Data: 2019-04-02). Mesmas palavras-chave: postgresql, banco relacional, kickoff 2019.
- **Ação recomendada**: não gerar um novo ADR para a escolha do Postgres; no máximo estender o ADR-002 com a evolução de versões (11, 13, 15, 16) e a menção ao RDS. Também há sobreposição com os potenciais de INFRA (`aws-como-hospedagem-e-pipeline-de-deploy.md`).

---

## O que foi identificado

O Horalis usa PostgreSQL como único banco relacional desde o primeiro commit (`c5c258b`, 2019-04-01: `.env.example` com `DB_CONNECTION=pgsql` e serviço `postgres:11` no compose). A ata do kickoff técnico de 2019-04-02 registra apenas "Banco: Postgres (o Rafael já deixou configurado)", sem alternativas comparadas nem motivo da escolha. Portanto o "porquê" original não está documentado; o que se sabe é que o time dominava a stack e o prazo do MVP era de três meses.

A escolha deixou de ser neutra com o tempo. O Postgres sustentou duas decisões estruturais: o isolamento por schema (2019, `search_path`) e a sua substituição por schema único com `tenant_id` (2022). Também aparece SQL específico do Postgres no código (`ilike` na busca de pacientes, `ALTER COLUMN ... TYPE text`). Em produção o banco roda no RDS (Postgres gerenciado, migração da VPS para a AWS concluída em 2021-06, segundo o Slack #arquitetura de 2021-06-07). A versão acompanhou o ambiente: 11 (2019), 13 (`ea99585`, 2022-01), 15 (`3962b4f`, 2023-04), 16 (`c83bd4a`, 2024-05); hoje o compose fixa `postgres:16.15-alpine` (`989d7cc`, 2025-11).

## Por que isso pode merecer um ADR

- **Impacto**: todo o sistema persiste no Postgres; fila, cache e sessão ficam no Redis, mas qualquer dado de negócio está aqui.
- **Trade-offs**: o time ganhou schemas, transações DDL, `ilike`, tipos ricos; em troca, `ilike '%termo%'` não usa índice comum e não tolera erro de digitação (ver ADR potencial sobre a busca revertida). Não há abstração para trocar de banco.
- **Complexidade**: a configuração `search_path` do `config/database.php` ficou como resquício do modelo antigo (`'search_path' => 'public'`).
- **Conhecimento do time**: quem entra precisa saber que o fuso do servidor de banco é UTC enquanto a aplicação grava horários locais (ver ADR potencial de fuso horário).
- **Implicações futuras**: a opção RLS foi descartada em 2022 "por falta de domínio do time", mas pode ser revisitada porque o Postgres a suporta.
- **Contexto temporal**: estável há mais de 6 anos.

## Evidências encontradas no código

### Arquivos-chave
- [`docker-compose.yml`](../../../../docker-compose.yml) (serviço `postgres`, linhas 48-61): imagem `postgres:16.15-alpine`, healthcheck, script de criação do banco `horalis_test`.
- [`config/database.php`](../../../../config/database.php) (linhas 18 e 66-80): `default = pgsql`; conexão com `search_path = public`.
- [`app/Http/Controllers/PacienteController.php`](../../../../app/Http/Controllers/PacienteController.php) linha 18: uso de `ilike` (específico do Postgres).
- [`docker/postgres/criar-banco-testes.sql`](../../../../docker/postgres/criar-banco-testes.sql): testes rodam em Postgres real (`5bedfc9`, 2025-10).

### Evidência de código
```php
// app/Http/Controllers/PacienteController.php:18
->when($request->filled('q'), fn ($query) => $query->where('nome', 'ilike', '%'.$request->input('q').'%'))
```

### Análise de impacto (git)
- Introduzido: 2019-04-01 (`c5c258b`).
- Trocas de versão da imagem: `ea99585` (11 para 13), `3962b4f` (13 para 15), `c83bd4a` (15 para 16), `989d7cc` (tag fixa).
- Atualização do RDS citada no Slack: 2024-02-07 (janela de 10 min), sem commit correspondente.
- Temas recentes: tag fixa de imagem, banco de teste separado, entrypoint que espera o Postgres (`eff7ee9`).

### Alternativas (se observáveis)
Nenhuma registrada para o banco em si. Alternativas registradas apenas para a organização dos dados (banco por clínica e RLS, ata de 2022-05-20).

## Questões a responder no ADR (se criado)

- Por que Postgres e não MySQL? (o contexto diz só que o Rafael "já deixou configurado"; confirmar com quem ficou, provavelmente "sempre foi assim".)
- RDS em instância única: o plano de alta disponibilidade e de backup é decisão ou ausência de decisão? (fora do repositório.)
- Política de versões (11 para 16 em 5 anos) e quem executa o upgrade no RDS.

## ADRs potenciais relacionados
- [Eloquent ORM e migrations](./eloquent-orm-e-migrations-laravel.md)
- [Schema único com tenant_id](./schema-unico-com-tenant-id-substitui-schema-por-clinica.md)
- [Schema por clínica (2019, substituída)](./schema-por-clinica-no-postgres-2019-substituida.md)
- [Busca de pacientes no Postgres](./busca-de-pacientes-no-postgres-meilisearch-revertido.md)

## Notas adicionais

- A produção (RDS, instância única segundo o postmortem de 2022-04-12) não está no repositório; o ADR deve citar o Slack e o postmortem como fonte, não o código.
- O módulo INFRA pode reivindicar a parte de hospedagem (RDS). Evitar duplicar.
