# ADR Potencial: Autenticação da API com Sanctum (token opaco por dispositivo) em vez de JWT

**Módulo**: API
**Categoria**: Segurança
**Prioridade**: Must Document (Score: 120)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe. Relacionado: [identificação do tenant na API](./identificacao-do-tenant-na-api-do-cabecalho-para-o-dono-do-token.md). O módulo AUTH do mapeamento cobre a autenticação por sessão do painel; este ADR trata apenas da API.

---

## O Que Foi Identificado

Pacientes se autenticam em `POST /api/v1/auth/token` com e-mail, senha e slug da clínica, e recebem um token Bearer do Laravel Sanctum, gerado por dispositivo (`createToken($dados['dispositivo'] ?? 'app')`). O token é opaco e fica persistido na tabela `personal_access_tokens`.

A discussão do Slack de 2021-02-17 mostra a decisão com alternativa descartada. Beatriz Nogueira propôs JWT com `tymon/jwt-auth`, que usava em empregos anteriores. Juliana Prado objetou que JWT stateless é difícil de revogar (paciente perde o celular, clínica bloqueia o paciente). Beatriz sugeriu blacklist em cache ou token curto com refresh; Rafael Lima respondeu que isso "reinventa sessão em cima de JWT" e que o Sanctum resolve: token opaco no banco, revogar é apagar a linha. Beatriz concordou, citando também que é oficial do Laravel (uma dependência de terceiro a menos).

No git: `4381c6b` (2021-03-02) instalou o Sanctum (`laravel/sanctum ^2.9`, `config/sanctum.php`, migration de `personal_access_tokens`, e a rota de exemplo passou de `auth:api` para `auth:sanctum`); `e75f608` (2021-03-04) criou o login do paciente por token, com coluna `senha` em `pacientes`. Em 2023-07-11 o upgrade para Laravel 10 (`40d1dc9`) adicionou a coluna `expires_at` a `personal_access_tokens` (migration `2023_07_11_140500`), mas `config/sanctum.php` continua com `'expiration' => null`: os tokens, na prática, nunca expiram. Não há motivo registrado para isso, e nenhum fluxo de revogação (logout, bloqueio de paciente) foi encontrado nas rotas atuais, apesar de a revogação ter sido o argumento central a favor do Sanctum.

## Por Que Isto Pode Merecer um ADR

- **Impacto**: define a segurança de acesso a dados de saúde de pacientes pelo app (LGPD).
- **Trade-offs**: ganho de revogabilidade e menos dependências, contra uma consulta ao banco por requisição autenticada e acoplamento ao Laravel.
- **Complexidade**: baixa no código, mas com lacuna entre o argumento (revogação) e a implementação (sem rota de revogação, sem expiração).
- **Conhecimento do time**: necessário para qualquer mudança no login ou no app; o motivo estava só no Slack.
- **Implicações futuras**: política de expiração e de revogação ainda não definida; a coluna `expires_at` sugere intenção de mudar que não foi concluída.
- **Contexto temporal**: estável desde 2021-03 (mais de 5 anos); ajuste de 2023-07.

## Evidências Encontradas no Código

### Arquivos-chave
- [`app/Http/Controllers/Api/V1/AuthController.php`](../../../../../app/Http/Controllers/Api/V1/AuthController.php) - emissão do token por dispositivo.
- [`config/sanctum.php`](../../../../../config/sanctum.php) - `expiration => null`.
- [`database/migrations/2023_07_11_140500_add_expires_at_to_personal_access_tokens_table.php`](../../../../../database/migrations/2023_07_11_140500_add_expires_at_to_personal_access_tokens_table.php)
- [`app/Models/Paciente.php`](../../../../../app/Models/Paciente.php) - `HasApiTokens` e senha.
- `tests/Feature/Api/AuthTokenTest.php` (criado em `9356b12`).

### Evidência de Código
```php
// AuthController::store
$token = $paciente->createToken($dados['dispositivo'] ?? 'app');
return response()->json(['token' => $token->plainTextToken, 'tipo' => 'Bearer', ...], 201);
```
```php
// config/sanctum.php
'expiration' => null,
```

### Análise de Impacto
- Introduzido: 2021-03-02 (`4381c6b`) e 2021-03-04 (`e75f608`).
- Modificado: migration `expires_at` em 2023-07 (`40d1dc9`); tipos de retorno no upgrade (`9e96e89`).
- Afeta: API e AUTH, modelo `Paciente`, app mobile.
- Temas: login por clínica, tenant pelo dono do token.

### Alternativas (observáveis)
- JWT com `tymon/jwt-auth`, com blacklist em cache ou refresh: descartado em 2021-02-17.

## Questões a Responder no ADR (se criado)

- Qual a política desejada de expiração e revogação de tokens? Por que `expiration` é `null`?
- Como um paciente perde acesso (bloqueio pela clínica, troca de aparelho)?
- Por que e-mail + senha + slug da clínica no login, e não código/convite?

## ADRs Potenciais Relacionados
- [Identificação do tenant na API](./identificacao-do-tenant-na-api-do-cabecalho-para-o-dono-do-token.md)
- [API REST versionada](./api-rest-versionada-na-url-no-monolito.md)

## Notas Adicionais
- Sem rate limit específico para o endpoint de login (apenas o limite global `api` de 60/min); não é decisão registrada.
- Enquadrado como infraestrutura de autenticação (domínio crítico, base 70).
