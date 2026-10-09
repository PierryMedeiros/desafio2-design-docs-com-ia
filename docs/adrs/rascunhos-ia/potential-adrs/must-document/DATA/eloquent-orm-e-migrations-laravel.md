# ADR Potencial: Eloquent (Active Record) como camada de acesso a dados e migrations do Laravel

**Módulo**: DATA
**Categoria**: Arquitetura / Tecnologia (Step 0, Categoria 3: ORM)
**Prioridade**: Documentar obrigatoriamente (Pontuação: 145 = 75 base + 25 escopo + 20 custo de mudança + 25 conhecimento)
**Data da identificação**: 2026-10-09

---

## O que foi identificado

O acesso a dados é feito pelo Eloquent, o ORM Active Record do Laravel, com migrations versionadas em `database/migrations` (26 arquivos de 2014 a 2023). Veio junto com o framework no commit inicial (`c5c258b`, 2019-04-01, Laravel 5.8) e o kickoff de 2019-04-02 justificou o Laravel pela experiência do time, não o ORM em si. O ORM nunca foi questionado nos registros.

Ele deixou de ser "apenas o padrão do framework" porque o modelo de multi-tenancy atual depende dele: a trait `BelongsToTenant` registra um escopo global do Eloquent (`TenantScope`) e preenche `tenant_id` no evento `creating` (`396964c`, 2022-06-07). O isolamento entre clínicas, portanto, vale para Eloquent e não para `DB::table` ou SQL cru, o que a ata de 2022-05-20 registra como risco (mitigação: testes de isolamento e revisão de `withoutGlobalScopes`/`DB::table`). Também dependem do ORM os casts `encrypted` (CPF e notas clínicas, `1be1d22`) e `hashed` (senha). Há consultas com `DB::raw` (relatório de faltas, `da7e7f6`, `c9c0c54`).

As migrations seguem o framework e foram mudando de forma: classes nomeadas (2019) e anônimas (2022 em diante); duas trilhas (`database/migrations` e `database/migrations/tenant`) entre 2019-05 (`7a4da6a`) e 2022-06 (`08fab1a`), voltando a uma só.

## Por que isso pode merecer um ADR

- **Impacto**: todos os modelos (`app/Models/*`, 10 classes), seeders, factories e testes.
- **Trade-offs**: produtividade e escopo global automático versus risco de vazar o escopo com SQL cru e comportamento "aberto por padrão" quando o contexto de tenant não está ativo (`TenantScope`, jobs e comandos).
- **Complexidade**: regras de negócio nos modelos (`Agendamento::alterarStatus`, `ativo()`); a decisão de colocar `Disponibilidade` num serviço próprio só veio em 2025 (`e3b2252`).
- **Conhecimento do time**: obrigatório para qualquer mudança em consulta ou migration.
- **Implicações futuras**: troca de ORM é inviável na prática; ampliar o uso de `DB::table` enfraquece o isolamento.
- **Contexto temporal**: estável há mais de 6 anos, acompanhou upgrades do framework (`5f57d55`, `2069974`, `9cae2ef`, `40d1dc9`).

## Evidências encontradas no código

### Arquivos-chave
- [`app/Tenancy/BelongsToTenant.php`](../../../../app/Tenancy/BelongsToTenant.php) e [`app/Tenancy/TenantScope.php`](../../../../app/Tenancy/TenantScope.php): escopo global do Eloquent.
- [`app/Models/Agendamento.php`](../../../../app/Models/Agendamento.php) (casts, linhas 48-53; `alterarStatus`, 75-87).
- [`app/Models/Paciente.php`](../../../../app/Models/Paciente.php): casts `encrypted` e `hashed`, hook `saving` calculando `cpf_hash`.
- [`app/Console/Commands/RelatorioFaltas.php`](../../../../app/Console/Commands/RelatorioFaltas.php): uso de `DB::raw` (ponto fora do escopo global automático).

### Evidência de código
```php
// app/Tenancy/TenantScope.php
if ($contexto->ativo()) {
    $builder->where($model->qualifyColumn('tenant_id'), $contexto->id());
}
```

### Análise de impacto (git)
- Introduzido: 2019-04-01 (`c5c258b`).
- Escopo global adicionado: 2022-06-07 (`396964c`); testes de isolamento: `f61f605`.
- Modelos tocados em 11 a 13 commits (`Paciente`, `Agendamento`).
- Temas: upgrades de framework, tipos de retorno (`9e96e89`), criptografia, tenant.

### Alternativas (se observáveis)
Nenhuma registrada.

## Questões a responder no ADR (se criado)

- Política sobre `DB::table`/`withoutGlobalScopes`: é regra formal ou convenção de revisão?
- O escopo aberto quando não há tenant ativo é intencional? (sem motivo registrado; o job de lembretes percorre `Tenant` e filtra por `tenant_id` explicitamente, enquanto `AvisarListaEspera` também filtra por `tenant_id` à mão.)
- Convenção de migrations: o que fazer com a estrutura herdada (`tenant_id` nullable, ver ADR do schema único).

## ADRs potenciais relacionados
- [PostgreSQL](./postgresql-como-banco-relacional.md)
- [Schema único com tenant_id](./schema-unico-com-tenant-id-substitui-schema-por-clinica.md)

## Notas adicionais

O framework (Laravel) como Step 0 de categoria 2 deve ser tratado em outro módulo (provavelmente fora de DATA); este ADR foca apenas na camada de dados.
