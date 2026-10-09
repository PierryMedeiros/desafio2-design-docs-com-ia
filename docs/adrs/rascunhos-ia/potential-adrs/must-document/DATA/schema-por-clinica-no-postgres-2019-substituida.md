# ADR Potencial (histórico, decisão substituída): Um schema PostgreSQL por clínica

**Módulo**: DATA (com sobreposição com TENANCY)
**Categoria**: Arquitetura / Dados / Segurança
**Prioridade**: Documentar obrigatoriamente (Pontuação: 140 = 70 base + 25 escopo + 25 custo de mudança + 20 conhecimento hoje) - registro necessário para explicar a decisão que a substituiu
**Data da identificação**: 2026-10-09
**Situação no código**: Decisão **substituída em 2022-06**; já não existe no código (restam só o `docs/ARQUITETURA.md` e o histórico git)

---

## Contexto de ADRs existentes

**DECISÃO SEMELHANTE JÁ FORMALIZADA** (detectado em 2026-10-09, após a identificação):
- **ADR-003**: Um schema por clínica no banco (`docs/adrs/ADR-003-um-schema-por-clinica-no-banco.md`, Status: Superseded, Data: 2019-04-02), que já aponta para o ADR-013 (schema único com `tenant_id`, ainda não gerado).
- **Também existe potencial equivalente em TENANCY**: `../TENANCY/isolamento-por-schema-por-clinica-2019.md`.
- **Ação recomendada**: não gerar este arquivo como ADR separado. Usar apenas como evidência complementar (commits `3b304c1`, `7a4da6a`, `797ec44`, `47031f4`, `827eee1`, `d2bbdab`) para o ADR-003.

---

## O que foi identificado

No kickoff técnico de 2019-04-02 (ata da Juliana Prado) decidiu-se isolar os dados de cada clínica em **um schema PostgreSQL por clínica** (`clinica_<slug>`), com o schema `public` guardando apenas `tenants` e `users`. A origem foi a orientação do advogado da empresa (Dr. Otávio): por tratar dados de saúde (sensíveis na LGPD), o isolamento devia ser "da forma mais forte que for viável", para que um erro de programação não expusesse pacientes de uma clínica para outra. A **alternativa considerada e descartada** foi um banco único com coluna `clinica_id` filtrada nas consultas, por risco de vazamento ("basta uma consulta sem o filtro"). Rafael anotou que seria preciso migrar todos os schemas e criar schema para clínica nova.

Implementação (todas de Rafael Lima): tabela `tenants` com coluna `schema` (`3b304c1`, 2019-05-06); `GerenciadorSchemas` e `DefinirSchemaTenant` com troca do `search_path` e migrations da clínica separadas em `database/migrations/tenant` (`7a4da6a`, 2019-05-08); `tenants:migrate` (`797ec44`, 2019-05-10) e `tenants:criar` (`47031f4`, 2019-05-17). Ajustes posteriores: progresso (`6d40f5d`, 2019-11), "segue se um schema falhar" (`827eee1`, 2020-12-02) e registro do tempo por schema (`d2bbdab`, 2022-04-20). O `docs/ARQUITETURA.md` (`fd9a487`, `dd800dc`, 2019-07) descreve esse modelo e nunca foi atualizado, por isso está desatualizado.

**Por que foi substituída**: ver ADR do schema único. Resumo: o postmortem de 2022-04-12 mostrou que o tempo do `tenants:migrate` crescia linearmente (cerca de 60 clínicas e 10 min em 2020; 380 schemas, 50 s cada e 5h30 em 2022), com estado misto durante o deploy (o commit `827eee1`, de 2020-12-02, fez o comando continuar quando um schema falhava, o que torna possível um estado misto; o motivo dessa escolha não está registrado).

## Por que isso pode merecer um ADR

- **Impacto**: moldou toda a base de código de 2019 a 2022 (middleware, migrations em duas pastas, jobs que precisavam trocar de schema, token do Sanctum dentro do schema da clínica, header `X-Clinica`).
- **Trade-offs**: isolamento forte versus custo operacional de migração linear no número de clínicas, o que só se manifestou em escala.
- **Conhecimento do time**: quem lê migrations antigas, o documento de arquitetura ou o Slack precisa saber que o modelo existiu e por que mudou.
- **Contexto temporal**: vigente de 2019-05 a 2022-06 (cerca de 3 anos). O `ARQUITETURA.md` ainda o apresenta como atual.

## Evidências encontradas no código

### Arquivos-chave (hoje só no histórico git)
- `app/Tenancy/GerenciadorSchemas.php` (versão `7a4da6a`) e `app/Http/Middleware/DefinirSchemaTenant.php`.
- `app/Console/Commands/TenantsMigrate.php` (`797ec44`, removido em `827b1b7`) e `CriarTenant.php`.
- [`docs/ARQUITETURA.md`](../../../../docs/ARQUITETURA.md): seção "Banco de dados e clínicas".
- [`contexto/atas/2019-04-02-kickoff-tecnico.md`](../../../../contexto/atas/2019-04-02-kickoff-tecnico.md), [`contexto/slack/arquitetura.md`](../../../../contexto/slack/arquitetura.md) (2019-05-14 e 2021-02-18).

### Evidência de código
```php
// GerenciadorSchemas (7a4da6a) - migrar cada schema
Artisan::call('migrate', ['--path' => 'database/migrations/tenant', '--force' => true]);
```

### Análise de impacto (git)
- Introduzido: 2019-05-06 a 2019-05-17 (`3b304c1`, `7a4da6a`, `797ec44`, `47031f4`).
- Removido: `827b1b7` (2022-06-28) e migration `2022_06_28_113000_remove_schema_from_tenants_table`.
- Temas: "tenancy", "search_path", "tenants:migrate".

### Alternativas
Coluna `clinica_id` (descartada em 2019; adotada em 2022 com `tenant_id`, quando o contexto mudou: escala, postmortem e criptografia de campo).

## Questões a responder no ADR (se criado)

- A orientação do advogado de 2019 foi reavaliada juridicamente em 2022? O e-mail da DPO diz que unificar "não é proibido", mas exige mitigações.
- Houve avaliação de custo do `tenants:migrate` antes de 2022? O postmortem diz que "ninguém percebeu a curva".

## ADRs potenciais relacionados
- [Schema único com tenant_id](./schema-unico-com-tenant-id-substitui-schema-por-clinica.md)
- [PostgreSQL](./postgresql-como-banco-relacional.md)

## Notas adicionais

Atenção: `docs/ARQUITETURA.md` ainda descreve este modelo como atual. O ADR deve apontar o documento como desatualizado (última atualização em julho/2019). O fato de os commits de 2019 não terem mensagem do motivo reforça o uso da ata do kickoff como fonte única.
