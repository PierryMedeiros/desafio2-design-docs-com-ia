# ADR Potencial: Multi-tenancy com schema único, `tenant_id` e escopo global na aplicação

**Módulo**: TENANCY
**Categoria**: Arquitetura / Segurança (isolamento de dados)
**Prioridade**: Must Document (Score: 150)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe ainda (`docs/adrs/generated/` não existe). Esta decisão **substitui** a decisão de 2019 descrita em [isolamento-por-schema-por-clinica-2019.md](./isolamento-por-schema-por-clinica-2019.md) (par obrigatório: documentar os dois juntos, com status "Substituída" no mais antigo).

Relacionadas: [identificacao-do-tenant-por-requisicao.md](./identificacao-do-tenant-por-requisicao.md), [escopo aberto sem contexto ativo](../../consider/TENANCY/escopo-aberto-sem-contexto-ativo-em-jobs-e-comandos.md) e, no módulo PRIVACIDADE, a criptografia de campo (`1be1d22`, `98ff77d`), que foi condição para esta decisão.

---

## O Que Foi Identificado

Desde junho de 2022 o Horalis guarda os dados de todas as clínicas nas mesmas tabelas do schema `public`. Cada tabela da clínica (`profissionais`, `servicos`, `disponibilidades`, `bloqueios`, `pacientes`, `agendamentos`, `anexos` e, depois, `lista_espera`) tem uma coluna `tenant_id`. O isolamento é feito pela aplicação: a trait `BelongsToTenant` registra o `TenantScope` (global scope do Eloquent, que adiciona `where tenant_id = <clínica atual>`) e preenche `tenant_id` no evento `creating`. O tenant corrente fica em `TenantContext`, um singleton por requisição.

A decisão foi tomada na reunião de 2022-05-20 (`contexto/atas/2022-05-20-reuniao-tenancy.md`), como cumprimento da ação "Avaliar o modelo de multi-tenancy" do postmortem de 2022-04-12. O motivo real foi o deploy: o `tenants:migrate` levou cerca de 5h30 em 380 schemas e deixou parte das clínicas com erro 500 (estado misto). Foram avaliadas e descartadas: (a) manter schema por clínica com migrations em paralelo (protótipo: de 5h30 para ~50 min, mas o tempo continua crescendo com o número de clínicas e o estado misto continua existindo); (b) um banco por clínica (piora migração e estoura limite de conexões do RDS); (c) schema único com Row Level Security (conflito com pool de conexões, risco de variável de sessão herdada, ninguém do time domina RLS; Rafael registrou que pode ser revisitada). Escolhida: (d) `tenant_id` + escopo global.

Linha do tempo no git: escopo global e contexto em 2022-06-07 (`396964c`), migration com `tenant_id` em 2022-06-09 (`08fab1a`), comando de cópia dos dados em 2022-06-14 (`f60eb60`), testes de isolamento em 2022-06-21 (`f61f605`), remoção do schema por clínica em 2022-06-28 (`827b1b7`), remoção dos comandos já executados em 2022-08-09 (`d7a7363`). No Slack (#arquitetura, 2022-06-27) Thiago registra a "janela de sábado concluída, contagens batendo em todas as tabelas". O sábado em questão é inferido (25/06/2022); a data exata da janela não está nos commits.

## Por Que Isto Merece um ADR

- **Impacto**: toda leitura e escrita do sistema passa pelo escopo; todo model de clínica depende da trait. É a única barreira de isolamento entre dados de saúde de clínicas diferentes.
- **Trade-offs**: ganhos: uma migration roda uma vez, sem estado misto; menos conexões e metadados. Perdas: a separação física deixou de existir, o isolamento depende de disciplina de código (SQL cru, `DB::table`, `withoutGlobalScopes` escapam do escopo); dados sensíveis em texto claro passaram a pesar mais (por isso a condição da DPO).
- **Complexidade**: baixa no código (~3 arquivos), alta nas consequências.
- **Conhecimento da equipe**: todo desenvolvedor precisa saber que um model novo de clínica DEVE usar `BelongsToTenant` e ter `tenant_id`.
- **Implicações futuras**: RLS foi deixada aberta para o futuro; migração para outro modelo seria de meses.
- **Contexto temporal**: estável há mais de 4 anos (2022-06 a 2026).

## Evidências Encontradas no Código

### Arquivos-chave
- [`app/Tenancy/BelongsToTenant.php`](../../../../../app/Tenancy/BelongsToTenant.php) - trait; registra o escopo e preenche `tenant_id` no `creating`.
- [`app/Tenancy/TenantScope.php`](../../../../../app/Tenancy/TenantScope.php) - filtro por `tenant_id`.
- [`app/Tenancy/TenantContext.php`](../../../../../app/Tenancy/TenantContext.php) - estado do tenant corrente.
- [`database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php`](../../../../../database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php) - coluna e índice; `unique(tenant_id, email)` em `pacientes`.
- [`database/migrations/2022_06_28_113000_remove_schema_from_tenants_table.php`](../../../../../database/migrations/2022_06_28_113000_remove_schema_from_tenants_table.php) - remove `tenants.schema`.
- [`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`](../../../../../tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php) - mitigação acordada na ata.
- [`docs/postmortems/2022-04-12-deploy-travado.md`](../../../../postmortems/2022-04-12-deploy-travado.md) - causa raiz.

### Evidência de código
```php
// app/Tenancy/TenantScope.php
if ($contexto->ativo()) {
    $builder->where($model->qualifyColumn('tenant_id'), $contexto->id());
}
```

### Análise de Impacto
- Introduzido: 2022-06-07 (`396964c`); schema por clínica removido em 2022-06-28 (`827b1b7`, cerca de 27 arquivos tocados).
- Pouca mudança em `app/Tenancy/*` depois: apenas reformatação (`97ab0bf`, `9e96e89`) e testes (`e5cb391`, 2024-09). Estável.
- Adoção posterior confirma o padrão: `ListaEspera` (`7af58ab`, 2023-08) usa a trait e `tenant_id` NOT NULL.
- Temas: "tenant", "isolamento", "schema por clínica".

### Alternativas (documentadas na ata de 2022-05-20)
Migração paralela por schema, banco por clínica, schema único com RLS, schema único com `tenant_id` (escolhida).

## Perguntas a Responder no ADR

- Confirmar com quem participou (Rafael saiu em 06/2024; Marcos, Thiago, Beatriz ainda estão?) se houve outros critérios além dos da ata.
- Qual o plano atual para detectar uso indevido de `withoutGlobalScopes` / `DB::table`? A "revisão obrigatória" prometida na ata não tem evidência no repositório (sem lint, sem CI específica).
- RLS ainda é uma opção de defesa em profundidade?
- Ver nota abaixo sobre o motivo divergente (economia de RDS).

## ADRs Potenciais Relacionados
- [isolamento-por-schema-por-clinica-2019.md](./isolamento-por-schema-por-clinica-2019.md) (substituída por esta)
- [identificacao-do-tenant-por-requisicao.md](./identificacao-do-tenant-por-requisicao.md)
- [escopo-aberto-sem-contexto-ativo-em-jobs-e-comandos.md](../../consider/TENANCY/escopo-aberto-sem-contexto-ativo-em-jobs-e-comandos.md)

## Notas Adicionais

- **Narrativa divergente**: em #geral (2023-10-17) Helena afirma que a unificação "foi pra economizar no RDS" e Thiago confirma que a conta caiu. A ata de 2022-05-20 diz que a economia foi efeito colateral e que Rafael pediu para NÃO vender a mudança como economia. O ADR deve registrar o motivo real (deploy lento e estado misto) e citar a economia só como consequência.
- **Condição de segurança**: a unificação só ocorreu depois da criptografia de campo de `cpf` e `notas_clinicas` + `cpf_hash` (e-mail da DPO de 2022-05-03; ata). Registrar como decisão acoplada.
- **Consequência de migração**: o comando `tenancy:unificar-schemas` (`f60eb60`) não copiou os tokens do app ("os pacientes vão precisar entrar de novo"). Inferência: pacientes do app tiveram de refazer login após a janela; não há confirmação em outra fonte.
- **Inconsistência de integridade**: `tenant_id` foi criado `nullable` nas tabelas migradas (`08fab1a`), enquanto `lista_espera` (2023) nasceu NOT NULL. Não achei motivo registrado (provável transição de dados). Verificar se algum NOT NULL foi aplicado depois (não há migration).
- **Pendências da DPO sem evidência no repositório**: atualização do RIPD e restrição de acesso direto ao banco de produção.
- **Falha de documentação**: `docs/ARQUITETURA.md` ainda descreve o modelo antigo.
