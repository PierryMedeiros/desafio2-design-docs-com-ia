# ADR Potencial: Paciente como segundo tipo de identidade, com senha própria cadastrada pela recepção

**Módulo**: AUTH
**Categoria**: Segurança / Arquitetura
**Prioridade**: Considerar (Pontuação: 95)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existente. Relacionados (potenciais): `tokens-sanctum-por-dispositivo-em-vez-de-jwt`, `identificacao-do-tenant-pelo-dono-do-token`.

---

## O Que Foi Identificado

Existem duas identidades distintas: a equipe (`users`, sessão) e o paciente (`pacientes`, token). O `docs/ARQUITETURA.md` (2019) afirmava que pacientes não acessavam o sistema; com a API (2021) o próprio `Paciente` virou entidade autenticável com coluna `senha` (`e75f608`, 2021-03-04, migração `2021_03_04_111500_add_senha_to_pacientes_table.php`), cast `hashed` e `Hash::check` no login.

Não há cadastro nem recuperação de senha pelo próprio paciente: a recepção define a "senha do app" na ficha do paciente (`7e10085`, 2021-04-20, campo `senha`, mínimo 6 caracteres, `Hash::make`). O login exige e-mail, senha e slug da clínica; o e-mail do paciente é único por clínica (`unique(['tenant_id','email'])`, migração `2022_06_09_162000`), ao contrário de `users`, único globalmente. Falha de login responde 422 com mensagem genérica, sem diferenciar clínica inexistente de senha errada (`test_senha_errada_ou_clinica_errada_respondem_422`).

## Por Que Isto Pode Merecer um ADR

- **Impacto**: define quem pode acessar dados de saúde pelo app e como a credencial é provisionada.
- **Trade-offs**: simples e sob controle da clínica, mas sem fluxo de self-service, sem verificação de e-mail e sem limite de tentativas explícito (nenhum `throttle` aplicado em `routes/api.php`).
- **Conhecimento da equipe**: importante para quem trabalha em API/app.
- **Implicações futuras**: recuperação de senha, MFA, política de senha.
- **Contexto temporal**: estável desde 2021; sem motivo registrado para a recepção definir a senha (candidato a "sempre foi assim").

## Evidências Encontradas no Código

### Arquivos-chave
- [`app/Models/Paciente.php`](../../../../app/Models/Paciente.php) - `senha` em `$fillable`, `$hidden` e cast `hashed`
- [`app/Http/Controllers/Api/V1/AuthController.php`](../../../../app/Http/Controllers/Api/V1/AuthController.php) - linhas 17-34
- [`app/Http/Controllers/PacienteController.php`](../../../../app/Http/Controllers/PacienteController.php) - criação com hash da senha
- [`database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php`](../../../../database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php) - linha 29

### Evidência de código
```php
if (! $paciente || ! $paciente->senha || ! Hash::check($dados['senha'], $paciente->senha)) {
    return response()->json(['message' => 'Credenciais inválidas.'], 422);
}
```

### Análise de Impacto
- Introduzido: `e75f608` (2021-03-04), `7e10085` (2021-04-20)
- Afeta: AUTH, API, PAINEL, DATA

### Alternativas (se observáveis)
Não registradas.

## Questões a Responder no ADR (se criado)

- Por que a senha é definida pela recepção e não pelo paciente?
- Como o paciente troca/recupera a senha?
- A unicidade de e-mail por clínica é intencional para pacientes e global para a equipe?

## ADRs Potenciais Relacionados
- [Tokens Sanctum](../../must-document/AUTH/tokens-sanctum-por-dispositivo-em-vez-de-jwt.md)
- [Identificação do tenant](../../must-document/AUTH/identificacao-do-tenant-pelo-dono-do-token.md)

## Notas Adicionais
Pontuação: base 70 + escopo 10 + custo 10 + conhecimento 5 = 95. Cuidado: CPF (criptografado) não participa do login; ver módulo PRIVACIDADE.
