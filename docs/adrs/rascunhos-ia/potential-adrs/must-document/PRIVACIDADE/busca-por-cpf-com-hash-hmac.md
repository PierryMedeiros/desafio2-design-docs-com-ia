# ADR Potencial: Índice cego `cpf_hash` (HMAC-SHA256 com chave separada) para busca por CPF

**Módulo**: PRIVACIDADE
**Categoria**: Segurança / Arquitetura de dados
**Prioridade**: Documentar (Pontuação: 120)
**Data de identificação**: 2026-10-09

---

## O que foi identificado

Como o CPF fica cifrado (e a cifra do Laravel não é determinística, então não permite `WHERE cpf = ?`), o sistema grava ao lado dele a coluna `pacientes.cpf_hash` (`string(64)`, indexada, não única), calculada por `HashCpf::gerar()`: remove tudo que não é dígito e aplica `hash_hmac('sha256', digitos, config('app.cpf_hash_key'))`. A chave é `CPF_HASH_KEY` (variável de ambiente, lida em `config/app.php`), **separada da `APP_KEY`** e do banco. O hash é recalculado no evento `saving` do model quando `cpf` muda. A busca de paciente por CPF (`PacienteController::busca`) e a checagem de duplicidade no cadastro (`store`) comparam `cpf_hash`.

Introduzido em 2022-05-12 (commit `98ff77d`, "busca de paciente por cpf com hash"), dois dias após a criptografia. A solução segue exatamente a sugestão da DPO no e-mail de 2022-05-03 (item 3): um hash com chave, mesmo resultado para o mesmo CPF, não reversível, com a chave fora do banco. A recepção busca por CPF o tempo todo, então a busca não podia ser sacrificada. Testes em `tests/Unit/HashCpfTest.php` (commit `0b019de`) garantem que formatos diferentes do mesmo CPF geram o mesmo hash de 64 caracteres.

## Por que isto pode merecer um ADR

- **Impacto**: é o único caminho de busca por CPF no painel e também sustenta a regra de CPF duplicado.
- **Trade-offs visíveis**:
  - igualdade apenas (sem busca parcial por prefixo de CPF);
  - o espaço de CPFs é pequeno (11 dígitos, com dígito verificador), então a segurança depende inteiramente do sigilo da `CPF_HASH_KEY`;
  - a chave **não** inclui `tenant_id`/sal por clínica: o mesmo CPF gera o mesmo hash em clínicas diferentes (útil para correlação, mas também revela que a mesma pessoa existe em duas clínicas a quem tiver acesso ao banco). Isto não aparece registrado como escolha deliberada;
  - o índice não é único; a unicidade é checada em código, e só dentro do escopo do tenant (escopo global).
- **Complexidade**: média; **rotação da chave** exige recalcular todos os hashes (não há comando para isso).
- **Conhecimento do time**: quem criar importação, API de busca ou seed precisa gerar o hash com `HashCpf` e não pode procurar por `cpf`.
- **Contexto temporal**: estável desde 2022-05 (4+ anos), sem alterações relevantes no `HashCpf`.

## Evidências encontradas no código

### Arquivos-chave
- [`app/Criptografia/HashCpf.php`](../../../../../app/Criptografia/HashCpf.php) - cálculo do HMAC
- [`app/Models/Paciente.php`](../../../../../app/Models/Paciente.php) - hook `saving` que recalcula o hash
- [`app/Http/Controllers/PacienteController.php`](../../../../../app/Http/Controllers/PacienteController.php) - `busca()` e `store()`
- [`database/migrations/2022_05_12_110000_add_cpf_hash_to_pacientes_table.php`](../../../../../database/migrations/2022_05_12_110000_add_cpf_hash_to_pacientes_table.php)
- [`config/app.php`](../../../../../config/app.php) (`cpf_hash_key`) e [`.env.example`](../../../../../.env.example) (`CPF_HASH_KEY` de desenvolvimento, "chave fixa de desenvolvimento")

### Evidência de código
```php
// app/Criptografia/HashCpf.php
return hash_hmac('sha256', $digitos, (string) config('app.cpf_hash_key'));
```

### Análise de impacto
- Introduzido: 2022-05-12 (`98ff77d`); testes em 2022-05-24 (`0b019de`)
- Origem: e-mail da DPO de 2022-05-03; ata de 2022-05-20 registra "coluna `cpf_hash` (HMAC-SHA256 com chave própria)"
- Afeta: cadastro, busca e duplicidade de pacientes; seeders (usam a chave de dev)

### Alternativas (observáveis)
- Hash simples sem chave (SHA-256 puro): descartado implicitamente pela DPO (hash "com chave"). Não há outros comparativos nos commits.
- Busca por nome continua em SQL (`ilike`) porque nome não é cifrado; uma POC de busca com Scout/Meilisearch foi feita e revertida em 2023-06 (`a08461f`, `0494bd3`, `8629f57`, revertidos por `a9de541`, `307e564`, `c99efbb`), mas por custo operacional, não por privacidade. Vale registrar no ADR de escopo que o índice indexou dados de pacientes num serviço fora do banco.

## Questões a responder no ADR (se criado)

- Por que HMAC-SHA256 e não outro esquema (HMAC-SHA512, Argon2 com sal, hash por tenant)?
- Onde fica a `CPF_HASH_KEY` em produção e quem tem acesso? (o e-mail exige separação do banco)
- Aceitamos hash igual entre clínicas? Há plano de rotação?

## ADRs potenciais relacionados
- [Criptografia de campo](./criptografia-de-campo-cpf-e-notas-clinicas.md)
- [Gestão de chaves](../../consider/PRIVACIDADE/gestao-de-chaves-app-key-e-cpf-hash-key.md)

## Notas adicionais
- Pontuação: base 70 (domínio crítico, busca sustentada por exigência LGPD) + 15 (escopo) + 15 (custo de mudança) + 20 (conhecimento) = 120.
- O mapeamento (mapping.md) cita "blind index" como padrão; é o nome técnico desta técnica.
