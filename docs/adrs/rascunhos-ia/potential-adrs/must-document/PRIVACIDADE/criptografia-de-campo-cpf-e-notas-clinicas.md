# ADR Potencial: Criptografia em nível de campo (cast `encrypted`) para CPF e notas clínicas

**Módulo**: PRIVACIDADE
**Categoria**: Segurança
**Prioridade**: Documentar (Pontuação: 135)
**Data de identificação**: 2026-10-09

---

## O que foi identificado

CPF do paciente e notas clínicas do agendamento são gravados criptografados pela própria aplicação, campo a campo, usando o cast `encrypted` do Eloquent (Laravel `Crypt`, cifra derivada da `APP_KEY`). Quem consulta a tabela direto vê apenas texto embaralhado; só a aplicação lê o valor. Os campos são `pacientes.cpf` (`Paciente`) e `agendamentos.notas_clinicas` (`Agendamento`). Para caber o texto cifrado, a coluna `cpf` passou de `varchar(14)` para `text`.

A decisão foi introduzida em 2022-05-10 (commit `1be1d22`, "criptografia de cpf e notas clínicas") e estava em produção em 2022-05-17, segundo a ata de 2022-05-20. Não foi iniciativa espontânea do time: nasceu do e-mail da DPO (Paula Mendes, consultoria externa) de 2022-05-03, que classificou notas clínicas como dado de saúde sensível e afirmou que a criptografia em repouso do RDS (disco) **não basta**, porque não protege contra acesso pela aplicação, consultas mal escritas ou rotinas com acesso ao banco. Rafael Lima confirmou em 2022-05-04 que a criptografia viria antes de qualquer unificação do banco. O código não cita esse motivo em nenhum comentário; o rastro está só em `contexto/`.

Desde a introdução o código mudou pouco (poucos commits tocam `Paciente.php`/`Agendamento.php` nesse aspecto): o cast permanece e `cpf`/`cpf_hash` foram colocados em `$hidden` para não vazar em serialização (API/JSON). O CPF só é exibido formatado na ficha do paciente (`cpfFormatado()`).

## Por que isto pode merecer um ADR

- **Impacto**: atinge todo acesso a paciente e agendamento (painel, API, jobs de lembrete). Vale para dados de saúde (LGPD, dado sensível).
- **Trade-offs visíveis**: o campo cifrado não pode ser filtrado nem ordenado em SQL (por isso existe o `cpf_hash`, ver ADR relacionado); `notas_clinicas` não é pesquisável; cifra e decifra por registro custam CPU; um dump do banco é inútil sem a chave, mas a chave vem da mesma `APP_KEY` que protege cookies e sessão.
- **Complexidade**: baixa no código (2 linhas de cast), alta nas consequências (migração de dados, chave, busca).
- **Conhecimento do time**: quem mexer em consulta, relatório, seed, `DB::table` ou migração precisa saber que `cpf` e `notas_clinicas` não podem ser lidos ou escritos em SQL cru sem passar pelo model.
- **Implicações futuras**: troca/rotação de `APP_KEY` hoje inutilizaria todos os dados cifrados (ver ADR "consider" sobre gestão de chaves); qualquer novo campo sensível precisa seguir o mesmo padrão.
- **Contexto temporal**: estável há mais de 4 anos (2022-05 a 2026-10).

## Evidências encontradas no código

### Arquivos-chave
- [`app/Models/Paciente.php`](../../../../../app/Models/Paciente.php) - cast `'cpf' => 'encrypted'`, `$hidden` com `cpf` e `cpf_hash`
- [`app/Models/Agendamento.php`](../../../../../app/Models/Agendamento.php) - cast `'notas_clinicas' => 'encrypted'`
- [`database/migrations/2022_05_10_142000_altera_cpf_para_text_em_pacientes.php`](../../../../../database/migrations/2022_05_10_142000_altera_cpf_para_text_em_pacientes.php) - `cpf` para `text`
- [`tests/Feature/CriptografiaTest.php`](../../../../../tests/Feature/CriptografiaTest.php) - garante que o valor bruto no banco não contém o texto claro (commit `0b019de`)

### Evidência de código
```php
// app/Models/Paciente.php
protected $casts = [
    'cpf' => 'encrypted',
    ...
];
// app/Models/Agendamento.php
'notas_clinicas' => 'encrypted',
```

### Análise de impacto
- Introduzido: 2022-05-10 (`1be1d22`); conversão dos dados existentes em 2022-05-17 (`1896d92`); testes em 2022-05-24 (`0b019de`)
- Contexto de origem: e-mail da DPO de 2022-05-03 e ata de 2022-05-20 (`contexto/emails/2022-05-03-dpo-criptografia.md`, `contexto/atas/2022-05-20-reuniao-tenancy.md`)
- Anterior: até 2022-05, CPF e notas ficavam em texto claro (coluna `varchar(14)` criada em 2019, commit `f29ed12`); a proteção vinha do isolamento por schema por clínica (decisão de 2019, ver ADR relacionado)
- Afeta: 2 models, painel, API, seeders; módulos PRIVACIDADE, PAINEL, API, TENANCY, DATA

### Alternativas (observáveis)
- Criptografia em repouso do RDS: já existia e foi mantida, mas considerada insuficiente pela DPO (e-mail de 2022-05-03). Não é alternativa, é camada complementar.
- Nenhuma outra alternativa (ex.: criptografia no banco com pgcrypto, KMS/envelope) aparece nos commits ou no contexto. Não há registro de que tenham sido avaliadas.

## Questões a responder no ADR (se criado)

- Por que cast do framework (chave única) e não pgcrypto/KMS com envelope encryption?
- Por que apenas `cpf` e `notas_clinicas`? O que ficou de fora e por quê (ver ADR "consider" sobre escopo)?
- Qual o plano de rotação/recuperação da `APP_KEY`?
- Qual é o custo aceito de não poder filtrar por esses campos em SQL?

## ADRs potenciais relacionados
- [Índice cego cpf_hash (HMAC-SHA256)](./busca-por-cpf-com-hash-hmac.md)
- [Criptografia como condição da unificação do banco](./criptografia-como-condicao-da-unificacao-do-banco.md)
- [Conversão dos dados existentes](../../consider/PRIVACIDADE/conversao-dos-dados-existentes-pacientes-criptografar.md)
- [Gestão da chave APP_KEY](../../consider/PRIVACIDADE/gestao-de-chaves-app-key-e-cpf-hash-key.md)

## Notas adicionais
- A pontuação não parte de uma categoria universal do Passo 0; foi tratada como infraestrutura de domínio crítica (dado de saúde sob LGPD), base 70, mais 20 (escopo) + 20 (custo de mudança) + 25 (conhecimento do time) = 135.
- A narrativa informal no Slack (#geral, 2023-10-17) sobre o motivo da unificação do banco diverge da ata; para privacidade, a fonte confiável é o e-mail da DPO e a ata de 2022-05-20.
