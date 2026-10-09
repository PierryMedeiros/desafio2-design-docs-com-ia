# ADR-012: Criptografia de campo para CPF e notas clínicas, com hash do CPF para busca

- **Status:** Accepted
- **Data:** 2022-05-04
- **Decisores:** Rafael Lima (CTO), Helena Duarte (CEO), por orientação de Paula Mendes (DPO, consultoria externa)
- **Relações:** nenhuma relação de substituição, emenda ou dependência. A [ADR-013](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md) depende desta.

## Contexto e problema

Depois do incidente de 2022-04-12, o time passou a avaliar a unificação dos dados das clínicas, que até ali tinham um schema cada ([ADR-003](ADR-003-um-schema-por-clinica-no-banco.md)). Rafael e Helena consultaram a DPO sobre essa mudança. A resposta dela, de 2022-05-03, diz que unificar "não é proibido, mas muda o nível de proteção que hoje vem 'de graça' pela separação" (`contexto/emails/2022-05-03-dpo-criptografia.md`).

Segundo o e-mail, a criptografia de disco do banco gerenciado já estava ativa, mas protege contra outro risco (alguém levar o disco ou um backup). Ela não protege contra o acesso pela aplicação nem contra "uma consulta mal escrita que traga registros de outra clínica". E a recepção busca paciente por CPF o tempo todo, então a busca precisava continuar funcionando.

## Opções consideradas

1. **Criptografia em nível de campo, feita pela aplicação**, para as notas clínicas e o CPF, com um hash com chave do CPF para a busca. É a recomendação da DPO.
2. **Apoiar-se só na criptografia em repouso do banco gerenciado.** A DPO a descartou explicitamente: "A criptografia em repouso do banco gerenciado não basta".
3. **Needs Input:** as fontes não registram alternativas técnicas de implementação avaliadas pelo time, como `pgcrypto` no banco ou chaves num KMS. O e-mail descreve o quê, e a implementação com o cast `encrypted` do Laravel aparece direto na ata e no código.

## Decisão

Opção 1, aceita por Rafael em 2022-05-04: "Vamos fazer a criptografia de CPF e notas clínicas antes de qualquer unificação, com o hash para a busca por CPF do jeito que você descreveu". A decisão também fixa a ordem: criptografar e converter os dados existentes antes de unificar, e não "criptografar depois".

Como foi implementada (ata de 2022-05-20 e código):

- `pacientes.cpf` e `agendamentos.notas_clinicas` com o cast `encrypted` do Laravel (`1be1d22`, 2022-05-10). A coluna `cpf` passou a `text`.
- coluna `pacientes.cpf_hash` com HMAC-SHA256 dos dígitos do CPF, com chave própria `CPF_HASH_KEY`, separada da chave de criptografia (`98ff77d`, `app/Criptografia/HashCpf.php`). A busca por CPF compara o hash.
- comando `pacientes:criptografar` para converter os dados existentes (`1896d92`) e testes (`0b019de`).

Os casts e o `cpf_hash` estavam em produção desde 2022-05-17 (ata de 2022-05-20).

## Consequências

### Positivas

- Quem lê a tabela direto, ou uma consulta que traga registros de outra clínica, vê o CPF e as notas clínicas embaralhados. Só a aplicação, com a chave, consegue ler.
- A busca por CPF continua funcionando pelo `cpf_hash`, sem guardar o CPF em claro.
- Tirou o principal bloqueio de privacidade para a unificação do banco ([ADR-013](ADR-013-schema-unico-com-tenant-id-e-escopo-global.md)).

### Negativas

- Os campos criptografados não podem ser filtrados, ordenados nem indexados pelo conteúdo. Só a busca exata por CPF, via hash, é possível.
- Dependência crítica das chaves (`APP_KEY` para o cast e `CPF_HASH_KEY` para o hash). Perder a `APP_KEY` torna os dados ilegíveis, e trocá-la exige recriptografar. **Needs Input:** as fontes não dizem onde as chaves ficam em produção nem qual é a política de rotação.
- Só CPF e notas clínicas são protegidos. Nome, telefone, e-mail e data de nascimento ficam em claro. O recorte é o da DPO, e as fontes não registram se outros campos foram discutidos.
- Ficaram pendências que o repositório não permite verificar: a atualização do RIPD e a restrição e o registro do acesso direto ao banco, também recomendados no e-mail.

## Evidências

- Commits: `1be1d22`, `98ff77d`, `1896d92`, `0b019de`.
- Arquivos: `app/Models/Paciente.php`, `app/Models/Agendamento.php`, `app/Criptografia/HashCpf.php`, `app/Console/Commands/CriptografarDadosPacientes.php`, `database/migrations/2022_05_12_110000_add_cpf_hash_to_pacientes_table.php`, `tests/Feature/CriptografiaTest.php`.
- Rastros: `contexto/emails/2022-05-03-dpo-criptografia.md` (e-mail da DPO e resposta do Rafael de 2022-05-04), `contexto/atas/2022-05-20-reuniao-tenancy.md` (seção "Condição para a migração").
