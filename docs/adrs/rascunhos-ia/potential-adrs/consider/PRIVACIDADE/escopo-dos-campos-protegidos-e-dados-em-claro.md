# ADR Potencial: Escopo dos dados protegidos (apenas CPF e notas clínicas) e dados pessoais mantidos em claro

**Módulo**: PRIVACIDADE
**Categoria**: Segurança / Conformidade
**Prioridade**: Considerar (Pontuação: 78)
**Data de identificação**: 2026-10-09

---

## O que foi identificado

A criptografia de campo cobre somente `pacientes.cpf` e `agendamentos.notas_clinicas`, exatamente o que a DPO pediu. Permanecem em texto claro: nome, telefone, e-mail, data de nascimento, `convenio`, `link_teleconsulta` e o status do agendamento. A busca de pacientes por nome usa `ilike` direto no banco (`PacienteController::index`), o que depende de o nome ficar em claro. Nenhum documento registra por que apenas esses dois campos foram escolhidos nem se os demais foram avaliados. A senha do app do paciente usa hash (`Hash::make` desde 2021-04-20, commit `7e10085`; hoje cast `hashed`), o que é decisão de AUTH e não é duplicada aqui.

Também vale registrar a superfície de privacidade fora do banco: `cpf`, `cpf_hash` e `senha` estão em `$hidden` (não saem em JSON/API); a POC de Scout/Meilisearch (2023-05, commits `a08461f`, `0494bd3`, `8629f57`, revertida em 2023-06 por `a9de541`, `307e564`, `c99efbb`) chegou a indexar pacientes em um serviço externo ao banco, sem registro de avaliação de privacidade. Não há configuração de scrubbing de dados pessoais no Sentry visível em `config/sentry.php` (arquivo com padrões do SDK; não foi possível confirmar a política de envio de PII).

## Por que isto pode merecer um ADR

- Define o limite do que é "dado sensível" no produto e orienta decisões futuras (novo campo clínico? exame? telefone?).
- Documenta um risco aceito: dados de contato e agendamento em claro, e acesso direto ao banco (cujas restrições a DPO pediu) expõe esses dados.
- Escopo claramente de política, com pouca evidência de decisão explícita; por isso "considerar".

## Evidências encontradas no código

- [`app/Models/Paciente.php`](../../../../../app/Models/Paciente.php) (`$casts`, `$hidden`)
- [`app/Models/Agendamento.php`](../../../../../app/Models/Agendamento.php) (`$casts`)
- [`app/Http/Controllers/PacienteController.php`](../../../../../app/Http/Controllers/PacienteController.php) (busca por nome com `ilike`)
- [`config/sentry.php`](../../../../../config/sentry.php)

### Análise de impacto
- Definido junto com a criptografia em 2022-05-10 (`1be1d22`); sem alterações de escopo desde então.

## Questões a responder no ADR (se criado)

- Quais campos o time classifica como sensíveis e por quê?
- O que o Sentry e os logs podem receber (PII)?
- Algum futuro serviço externo de busca/indexação precisa de revisão da DPO?

## ADRs potenciais relacionados
- [Criptografia de campo](../../must-document/PRIVACIDADE/criptografia-de-campo-cpf-e-notas-clinicas.md)

## Notas adicionais
- Pontuação: 15 + 10 + 20 + 33 de contexto = 78, limite inferior. Descartado como ADR separado: o hash da senha (decisão de AUTH) e o opt-in de WhatsApp (`e1dc55f`, 2023-09-06; é consentimento, pertence a LEMBRETES).
