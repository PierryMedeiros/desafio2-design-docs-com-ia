# ADR Potencial: Criptografia de campo como contrapartida e pré-condição ao fim do isolamento físico por clínica

**Módulo**: PRIVACIDADE (relacionado a TENANCY)
**Categoria**: Segurança / Conformidade (LGPD)
**Prioridade**: Documentar (Pontuação: 110)
**Data de identificação**: 2026-10-09

---

## O que foi identificado

Esta é a decisão de **modelo de proteção** por trás das duas anteriores, e é a que mais corre risco de ser esquecida ou contada errado. Em 2019 o controle de privacidade foi o isolamento físico: um schema PostgreSQL por clínica (`clinica_<slug>`), por orientação do advogado da empresa, para que "um erro de programação não exponha pacientes de uma clínica para outra" (ata de 2019-04-02, seção 3; `docs/ARQUITETURA.md`). Implementado em 2019-05 (`7a4da6a`, "schema por clinica: ... search_path no middleware").

Em 2022, após o incidente do deploy de 5h30 com 380 schemas (postmortem de 2022-04-12), decidiu-se migrar para schema único com `tenant_id` e escopo global (ata de 2022-05-20; tratada no módulo TENANCY). Do ponto de vista da LGPD, a DPO avisou (e-mail de 2022-05-03) que a unificação não é proibida mas retira a proteção que vinha "de graça" da separação, e condicionou: (1) criptografia de campo para CPF e notas clínicas, (2) busca por CPF via hash com chave, (3) **unificar só depois** de a criptografia estar em produção e os dados existentes convertidos, (4) testes automatizados de isolamento entre clínicas, (5) atualizar o RIPD de 2021 e restringir/registrar acesso direto ao banco de produção. A ata de 2022-05-20 incorporou isso como "Condição para a migração".

A ordem real no git confirma a sequência: criptografia (`1be1d22`, 2022-05-10) e hash (`98ff77d`, 2022-05-12), conversão dos dados (`1896d92`, 2022-05-17), testes de isolamento (`f61f605`, 2022-06-21), cópia dos dados dos schemas (`f60eb60`, 2022-06-14) e remoção do schema por clínica (`827b1b7`, 2022-06-28; o Slack registra a janela de produção de sábado concluída em 2022-06-27).

## Por que isto pode merecer um ADR

- **Impacto**: define como a empresa cumpre a LGPD depois que o isolamento físico acabou: defesa em profundidade (escopo global + testes de isolamento + criptografia de campo + chave separada), em vez de uma barreira única.
- **Trade-offs**: ganho operacional (migrations rodam uma vez) em troca de maior risco de vazamento entre clínicas por falha de aplicação (`withoutGlobalScopes`, `DB::table`, SQL cru; `TenantScope` é omitido sem contexto, relevante em jobs e comandos).
- **Narrativa divergente**: no Slack (#geral, 2023-10-17) alguém atribui a unificação à economia de RDS; a ata registra que o motivo foi o deploy e a economia foi efeito colateral. O ADR deve registrar o motivo real e a condição de privacidade, que raramente é lembrada.
- **Decisão substituída**: o "isolamento por schema por motivo de LGPD" (2019) foi superado em 2022 e não existe mais no código; só o `docs/ARQUITETURA.md` (desatualizado, julho/2019) e a ata de 2019 o descrevem.
- **Pendências não verificáveis no código**: atualização do RIPD; restrição e registro de acesso direto ao banco de produção.

## Evidências encontradas no código

### Arquivos-chave
- [`app/Console/Commands/CriptografarDadosPacientes.php`](../../../../../app/Console/Commands/CriptografarDadosPacientes.php) - conversão pré-unificação
- [`tests/Feature/Tenancy`](../../../../../tests/Feature/Tenancy) - testes de isolamento entre clínicas (mitigação exigida)
- [`docs/ARQUITETURA.md`](../../../../../docs/ARQUITETURA.md) - descreve o estado anterior (schema por clínica por LGPD)
- `contexto/emails/2022-05-03-dpo-criptografia.md`, `contexto/atas/2019-04-02-kickoff-tecnico.md`, `contexto/atas/2022-05-20-reuniao-tenancy.md`, `docs/postmortems/2022-04-12-deploy-travado.md`

### Análise de impacto
- Controle original: 2019-05 (`7a4da6a`); removido em 2022-06-28 (`827b1b7`)
- Controles compensatórios: 2022-05-10 a 2022-06-21 (`1be1d22`, `98ff77d`, `1896d92`, `0b019de`, `f61f605`)
- Afeta: TENANCY, PRIVACIDADE, DATA, todos os models de clínica

### Alternativas (registradas no contexto)
- Unificar sem criptografia, ou criptografar "depois": a DPO recomendou expressamente contra. 
- Manter schema por clínica com migração paralela, banco por clínica, ou RLS: descartadas na ata de 2022-05-20 (detalhe no módulo TENANCY). Criptografia em repouso do RDS isoladamente: insuficiente.

## Questões a responder no ADR (se criado)

- Qual era o risco aceito ao trocar isolamento físico por escopo lógico e como cada controle o mitiga?
- Os testes de isolamento e a revisão de `withoutGlobalScopes`/`DB::table` ainda são cumpridos hoje? (a conversão usa `DB::table` deliberadamente)
- O RIPD foi atualizado? O acesso direto ao banco de produção foi restringido?

## ADRs potenciais relacionados
- [Criptografia de campo](./criptografia-de-campo-cpf-e-notas-clinicas.md)
- [Índice cego cpf_hash](./busca-por-cpf-com-hash-hmac.md)
- Módulo TENANCY: decisão de schema único com `tenant_id` (a ser tratada na análise daquele módulo)

## Notas adicionais
- Pontuação: base 70 + 15 + 15 + 10 = 110 (a decisão é de governança e depende de documentos fora do código; revisar se a equipe preferir consolidar este conteúdo no ADR de TENANCY, evitando duplicidade).
