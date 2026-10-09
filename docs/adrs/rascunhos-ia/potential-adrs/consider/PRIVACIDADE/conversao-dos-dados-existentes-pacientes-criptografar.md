# ADR Potencial: Conversão in-place e idempotente dos dados existentes (`pacientes:criptografar`)

**Módulo**: PRIVACIDADE
**Categoria**: Migração de dados / Segurança
**Prioridade**: Considerar (Pontuação: 80)
**Data de identificação**: 2026-10-09

---

## O que foi identificado

Para converter CPF e notas clínicas já gravados em texto claro, foi criado o comando Artisan `pacientes:criptografar` (commit `1896d92`, 2022-05-17). Ele percorre pacientes e agendamentos, cifra com `Crypt::encryptString`, preenche `cpf_hash` e é **idempotente**: detecta valores já cifrados tentando decifrá-los (`jaCriptografado`, captura `DecryptException`), então pode ser reexecutado sem dupla cifra. A primeira versão iterava todos os schemas de clínica (`Tenant` + `GerenciadorSchemas`); foi reescrita no commit `827b1b7` (2022-06-28) para o schema único, usando `DB::table` (sem escopo global, intencional).

A ata de 2022-05-20 mostra que a conversão foi pré-requisito da unificação e que faltava "confirmar que o comando de conversão rodou em todos os schemas sem pendência" (responsável Juliana Prado, prazo 2022-05-27). Não há confirmação no material de contexto.

## Por que isto pode merecer um ADR

- Documenta a estratégia de rollout (cast + comando + janela, em vez de migração com downtime), útil para qualquer novo campo sensível.
- A detecção "tentar decifrar" tem um limite: se a `APP_KEY` mudar, valores já cifrados parecerão texto claro e seriam cifrados de novo (dupla cifra). Isto é risco real e não está registrado.
- Escopo limitado (1 comando) e já executado; por isso não passa de "considerar". Se não houver novos campos sensíveis, pode ser incorporado como seção do ADR de criptografia de campo.

## Evidências encontradas no código

- [`app/Console/Commands/CriptografarDadosPacientes.php`](../../../../../app/Console/Commands/CriptografarDadosPacientes.php)
- [`tests/Feature/CriptografiaTest.php`](../../../../../tests/Feature/CriptografiaTest.php) - `test_comando_criptografa_dados_antigos`

### Análise de impacto
- Introduzido: 2022-05-17 (`1896d92`); reescrito: 2022-06-28 (`827b1b7`)
- Afeta: 2 tabelas; uso único em produção (histórico), reutilizável

## Questões a responder no ADR (se criado)

- Houve downtime ou janela? Como foi validada a conversão completa?
- O comando ainda deve existir no código? Qual o plano se a chave mudar?

## ADRs potenciais relacionados
- [Criptografia de campo](../../must-document/PRIVACIDADE/criptografia-de-campo-cpf-e-notas-clinicas.md)

## Notas adicionais
- Pontuação (sem Passo 0): 15 + 10 + 10 + 45 de contexto de domínio crítico = 80; o limite entre "considerar" e descarte é estreito.
