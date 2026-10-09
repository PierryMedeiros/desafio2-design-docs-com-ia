# ADR Potencial: Schema único com coluna tenant_id e escopo global (substitui o schema por clínica)

**Módulo**: DATA (com forte sobreposição com TENANCY)
**Categoria**: Arquitetura / Dados
**Prioridade**: Documentar obrigatoriamente (Pontuação: 145 = 70 base de infraestrutura crítica do domínio + 25 + 25 + 25)
**Data da identificação**: 2026-10-09

---

## Contexto de ADRs existentes

**POTENCIAL DUPLICADO** (detectado em 2026-10-09, após a identificação):
- O ADR-003 (Superseded) já aponta para um **ADR-013: Schema único com tenant_id e escopo global**, ainda inexistente.
- Existe o potencial equivalente `../TENANCY/schema-unico-com-tenant-id-e-escopo-global.md` (Score 150), que cobre a mesma decisão. Gerar um só ADR (preferencialmente o de TENANCY) e aproveitar daqui a parte de dados: `tenant_id` nullable, tokens não copiados, divergência de narrativa sobre economia do RDS.

---

## O que foi identificado

Em 2022 o time abandonou o isolamento físico por schema (`clinica_<slug>`) e passou a guardar todas as clínicas nas mesmas tabelas do schema `public`, distinguindo-as pela coluna `tenant_id`. A migration `2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica` (commit `08fab1a`, 2022-06-09) adicionou `tenant_id` (FK para `tenants`, **nullable**, com índice) às sete tabelas da clínica e a restrição `unique(tenant_id, email)` em `pacientes`. O filtro é feito pela aplicação: `BelongsToTenant` + `TenantScope` (`396964c`, 2022-06-07) e o middleware `IdentificarTenant` (`e75b0d0`, 2022-06-08). Os dados antigos foram copiados pelo comando `tenancy:unificar-schemas` (`f60eb60`, 2022-06-14), com remapeamento de IDs e `tenant_id` preenchido; a janela de produção foi num sábado e concluiu em 2022-06-27 "com contagens batendo" (Slack #arquitetura). A coluna `schema` de `tenants` e o código de schemas foram removidos em `827b1b7` (2022-06-28); o comando de cópia saiu depois (`d7a7363`, 2022-08-09).

**Motivo real (fontes: postmortem de 2022-04-12 e ata de 2022-05-20)**: o `tenants:migrate` levou 5h30 para passar por 380 schemas, deixando clínicas em estado misto (parte com a coluna nova, parte sem, com erro 500). Dois problemas deviam ser resolvidos: o tempo de migration crescendo com o número de clínicas e o estado misto durante o deploy. Alternativas avaliadas na ata: (a) migrations em paralelo por schema (50 min no protótipo; descartada por continuar crescendo e manter o estado misto), (b) um banco por clínica (descartada: piora migração e conexões), (c) schema único com RLS (descartada: conflito com pool de conexões e falta de domínio do time; "pode ser revisitada"), (d) `tenant_id` + escopo global (escolhida).

**Divergência de narrativa a tratar no ADR**: em #geral (2023-10-17) Helena afirma que a unificação "foi pra economizar no RDS" e Thiago confirma que a conta caiu. A ata de 2022-05-20 diz o contrário: a economia foi efeito colateral e Rafael pediu expressamente para não vender a mudança como economia. O ADR deve registrar o deploy como motivo e a economia como consequência não planejada.

**Condição imposta**: a DPO (e-mail de 2022-05-03) só aceitou a unificação depois da criptografia de campo de `cpf` e `notas_clinicas` e do `cpf_hash`, em produção desde 2022-05-17 (`1be1d22`, `98ff77d`, `1896d92`). Isso pertence ao módulo PRIVACIDADE, mas é consequência direta desta decisão.

## Por que isso pode merecer um ADR

- **Impacto**: todas as tabelas de clínica, todo job, comando e rota; a segurança dos dados de saúde passou de física para lógica.
- **Trade-offs**: migration roda uma vez (resolvida a causa do postmortem) versus um filtro esquecido pode expor outra clínica; riscos explícitos: SQL cru, `DB::table`, `withoutGlobalScopes`.
- **Complexidade**: ainda existem consequências sem decisão registrada: `tenant_id` continua **nullable** nas sete tabelas originais (a migration não o torna `NOT NULL`, nenhuma migration posterior o fez); `lista_espera` já nasce com `tenant_id` obrigatório; `TenantScope` não filtra quando não há contexto ativo; `personal_access_tokens` e os tokens do app **não** foram copiados (o comando avisa: "os pacientes vão precisar entrar de novo").
- **Conhecimento do time**: crítico; qualquer consulta nova precisa respeitar o escopo.
- **Implicações futuras**: RLS pode ser retomado; 900 clínicas ativas em 2025-02 (Slack #geral) tornam a decisão ainda mais pesada.
- **Contexto temporal**: em produção desde 2022-06-25 (sábado da janela); estável por mais de 4 anos.

## Evidências encontradas no código

### Arquivos-chave
- [`database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php`](../../../../database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php)
- [`database/migrations/2022_06_28_113000_remove_schema_from_tenants_table.php`](../../../../database/migrations/2022_06_28_113000_remove_schema_from_tenants_table.php)
- [`app/Tenancy/BelongsToTenant.php`](../../../../app/Tenancy/BelongsToTenant.php), [`TenantScope.php`](../../../../app/Tenancy/TenantScope.php), [`TenantContext.php`](../../../../app/Tenancy/TenantContext.php)
- [`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`](../../../../tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php) (criado em `f61f605`).
- [`docs/postmortems/2022-04-12-deploy-travado.md`](../../../../docs/postmortems/2022-04-12-deploy-travado.md), [`contexto/atas/2022-05-20-reuniao-tenancy.md`](../../../../contexto/atas/2022-05-20-reuniao-tenancy.md), [`contexto/emails/2022-05-03-dpo-criptografia.md`](../../../../contexto/emails/2022-05-03-dpo-criptografia.md).

### Evidência de código
```php
// database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php
$table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
$table->index('tenant_id');
```

### Análise de impacto (git)
- Sequência: `396964c` (escopo, 06-07) -> `e75b0d0` (middleware, 06-08) -> `08fab1a` (migration, 06-09) -> `f60eb60` (cópia, 06-14) -> `f61f605` (testes de isolamento, 06-21) -> `827b1b7` (remoção do schema, 06-28) -> `9356b12` (2022-07-12, API passa a identificar o tenant pelo dono do token em vez do cabeçalho `X-Clinica`) -> `d7a7363` (remoção do comando de cópia, 2022-08-09).
- Regressão ligada à mudança: o comando `lembretes:enfileirar` foi reescrito na unificação e passou a comparar horários em UTC; lembretes saíram 3h adiantados até o hotfix de 2022-11-08 (`81c9949`). Ver ADR de fuso horário.
- Temas: "unificar", "isolamento", "remove schema por clínica".

### Alternativas observáveis
Todas registradas na ata de 2022-05-20 (a, b, c acima). A alternativa histórica da coluna `clinica_id` foi descartada em 2019 pelo risco de vazamento; ver ADR do schema por clínica.

## Questões a responder no ADR (se criado)

- Por que `tenant_id` ficou nullable? É dívida ou intencional (linhas do momento da cópia)? Há linhas órfãs hoje?
- O escopo aberto sem contexto é aceito para jobs/comandos? Quem garante o filtro explícito (`where('tenant_id', ...)`) nesses pontos?
- O RIPD foi atualizado (ação "Avisar a Paula" da ata)? Não verificável no repositório.
- Reavaliar RLS depois que o time ganhou experiência?

## ADRs potenciais relacionados
- [Schema por clínica (2019, substituída)](./schema-por-clinica-no-postgres-2019-substituida.md)
- [PostgreSQL](./postgresql-como-banco-relacional.md)
- [Eloquent e migrations](./eloquent-orm-e-migrations-laravel.md)
- [Horários locais sem fuso](../../consider/DATA/horarios-locais-sem-fuso-no-banco-e-fuso-por-clinica.md)
- PRIVACIDADE (criptografia de campo e `cpf_hash`) e TENANCY: ainda não analisados.

## Notas adicionais

- Para o ADR formal, usar a ata e o postmortem como fonte do motivo, não a conversa de 2023-10-17.
- As migrations de 2019-2021 ainda existem no histórico e foram movidas duas vezes entre `database/migrations` e `database/migrations/tenant` (`7a4da6a`, `08fab1a`). Não há mais a pasta `tenant` no código atual.
