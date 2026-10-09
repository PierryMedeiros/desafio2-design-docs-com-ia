# ADR Potencial: Gestão das chaves de criptografia (`APP_KEY` e `CPF_HASH_KEY`)

**Módulo**: PRIVACIDADE
**Categoria**: Segurança
**Prioridade**: Considerar (Pontuação: 85)
**Data de identificação**: 2026-10-09

---

## O que foi identificado

As duas chaves que protegem os dados sensíveis são variáveis de ambiente: `APP_KEY` (cast `encrypted`, também cookies e sessão) e `CPF_HASH_KEY` (HMAC do CPF, `config/app.php`). A DPO exigiu que a chave do hash ficasse **separada do banco** (e-mail de 2022-05-03, item 3); no código isso se traduz em env. O `.env.example` traz chaves fixas de desenvolvimento ("chave fixa de desenvolvimento, o seed grava o hash do CPF com ela"); `CPF_HASH_KEY` foi adicionada em `98ff77d` (2022-05-12) e `.env.example` ajustado depois em `d1d3c8e` (2024-08-20).

Não existe no repositório nenhum mecanismo de rotação, versionamento de chave (key id), uso de cofre/KMS nem comando para recifrar ou recalcular hashes. Como `APP_KEY` serve também para cookies/sessão, trocá-la por qualquer outro motivo tornaria CPF e notas ilegíveis. Onde as chaves de produção ficam (Secrets Manager, arquivo de env na instância) não está documentado no repositório nem em `contexto/`.

## Por que isto pode merecer um ADR

- Custo de mudança alto: rotação exige recifrar todos os registros e recalcular `cpf_hash`.
- Risco operacional: perda da chave = perda irrecuperável dos dados cifrados; chave comprometida anula a proteção.
- Com a saída da maior parte do time original (2019-2024), o conhecimento de onde estão as chaves pode ter se perdido.
- É uma lacuna (decisão implícita ou ausente) mais do que uma decisão registrada; o ADR serviria para documentar o estado atual e o risco aceito.

## Evidências encontradas no código

- [`config/app.php`](../../../../../config/app.php) (`key`, `cpf_hash_key`), [`.env.example`](../../../../../.env.example)
- [`app/Criptografia/HashCpf.php`](../../../../../app/Criptografia/HashCpf.php)
- Ausência: nenhum comando de rotação (busca por `rotate`/`recifrar` sem resultados), apenas `pacientes:criptografar`.

### Análise de impacto
- Chave de hash: 2022-05-12 (`98ff77d`); sem alteração desde então no mecanismo.
- Afeta: todos os dados cifrados e todas as instâncias da aplicação (duas atrás do balanceador, segundo o Slack).

## Questões a responder no ADR (se criado)

- Onde as chaves vivem em produção, quem as acessa e como há backup?
- Qual o procedimento de rotação e o RTO se a chave for perdida?
- Vale separar `APP_KEY` de uma chave dedicada aos campos (`Crypt` com chave própria)?

## ADRs potenciais relacionados
- [Criptografia de campo](../../must-document/PRIVACIDADE/criptografia-de-campo-cpf-e-notas-clinicas.md)
- [Índice cego cpf_hash](../../must-document/PRIVACIDADE/busca-por-cpf-com-hash-hmac.md)

## Notas adicionais
- Pontuação: 20 (escopo) + 25 (custo) + 20 (conhecimento) + 20 de contexto crítico = 85. A evidência sobre produção é inexistente; confirmar com o time atual antes de escrever o ADR.
