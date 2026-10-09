# ADR Potencial: Laravel Sanctum (token opaco por dispositivo) em vez de JWT para a API do paciente

**Módulo**: AUTH
**Categoria**: Segurança / Tecnologia
**Prioridade**: Deve Documentar (Pontuação: 130)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existente. ⚠️ **POTENCIAL SOBREPOSIÇÃO**: o módulo API identificou o mesmo tema em [Sanctum vs JWT (módulo API)](../API/sanctum-token-opaco-por-dispositivo-em-vez-de-jwt.md) (alta similaridade de palavras-chave). Recomenda-se gerar um único ADR formal, escolhendo um módulo dono. Relacionados (potenciais): `identificacao-do-tenant-pelo-dono-do-token`, `credenciais-proprias-do-paciente-para-o-app`.

---

## O Que Foi Identificado

A API `/api/v1` autentica o paciente com Laravel Sanctum: `POST /api/v1/auth/token` valida e-mail, senha e slug da clínica e emite um token opaco (`createToken`, nome = dispositivo informado), guardado na tabela `personal_access_tokens`. As demais rotas usam `auth:sanctum`. Revogar é apagar a linha.

A decisão foi tomada no Slack #arquitetura em 2021-02-17: Beatriz Nogueira propôs `tymon/jwt-auth` (experiência de emprego anterior); Juliana Prado levantou a dificuldade de revogar JWT stateless (paciente perde o celular, clínica bloqueia o paciente); Beatriz sugeriu blacklist em cache ou token curto com refresh; Rafael Lima respondeu que isso "reinventa sessão em cima de JWT" e propôs Sanctum. Fechado: "REST, /api/v1, Sanctum com um token por dispositivo". Implementação: `4381c6b` (2021-03-02, instala Sanctum) e `e75f608` (2021-03-04, login do paciente por token). Em produção em 2021-03-24.

Evolução: a migração `2023_07_11_140500_add_expires_at_to_personal_access_tokens_table.php` (`40d1dc9`, upgrade para Laravel 10, 2023-07-11) adicionou `expires_at`, mas `config/sanctum.php` mantém `'expiration' => null` e o `AuthController` não define expiração: na prática os tokens não expiram. Não há comando de limpeza nem endpoint de logout/revogação na API atual (`routes/api.php`).

## Por Que Isto Pode Merecer um ADR

- **Impacto**: contrato de autenticação do app publicado nas lojas; o app 3.0 (2023) continua em `/api/v1`, então mudar é caro (versões antigas ficam instaladas por meses).
- **Trade-offs**: revogação simples e dependência oficial, ao custo de uma consulta ao banco por requisição; tokens sem expiração efetiva aumentam a janela de risco.
- **Complexidade**: baixa, mas a lacuna de expiração/revogação exige decisão explícita.
- **Conhecimento da equipe**: quem mexe na API ou no app precisa saber.
- **Implicações futuras**: eventual v2 da API, expiração/rotação de tokens, endpoint de logout.
- **Contexto temporal**: estável desde 2021-03.

## Evidências Encontradas no Código

### Arquivos-chave
- [`app/Http/Controllers/Api/V1/AuthController.php`](../../../../app/Http/Controllers/Api/V1/AuthController.php) - linhas 24-36
- [`routes/api.php`](../../../../routes/api.php) - linha 11 (`auth:sanctum`)
- [`config/sanctum.php`](../../../../config/sanctum.php) - linha 52 (`expiration` null)
- [`database/migrations/2023_07_11_140500_add_expires_at_to_personal_access_tokens_table.php`](../../../../database/migrations/2023_07_11_140500_add_expires_at_to_personal_access_tokens_table.php)
- [`contexto/slack/arquitetura.md`](../../../../contexto/slack/arquitetura.md) - mensagens de 2021-02-17 10:20 a 10:30
- [`tests/Feature/Api/AuthTokenTest.php`](../../../../tests/Feature/Api/AuthTokenTest.php)

### Evidência de código
```php
$token = $paciente->createToken($dados['dispositivo'] ?? 'app');
```

### Análise de Impacto
- Introduzido: 2021-03-02 (`4381c6b`) e 2021-03-04 (`e75f608`)
- Mudanças: tenant pelo dono do token (`9356b12`, 2022-07-12), coluna `expires_at` (`40d1dc9`, 2023-07-11)
- Afeta: módulos AUTH, API, TENANCY

### Alternativas (observadas na discussão)
- JWT com `tymon/jwt-auth` (descartado: revogação difícil).
- JWT com blacklist em cache ou refresh token (descartado: "reinventa sessão").

## Questões a Responder no ADR (se criado)

- Qual a política desejada de expiração/rotação dos tokens? Por que `expires_at` foi criado sem uso?
- Como o paciente/clínica revoga um dispositivo hoje?
- Há limite de tokens por paciente?

## ADRs Potenciais Relacionados
- [Identificação do tenant pelo dono do token](./identificacao-do-tenant-pelo-dono-do-token.md)
- [Credenciais próprias do paciente](../../consider/AUTH/credenciais-proprias-do-paciente-para-o-app.md)

## Notas Adicionais
Pontuação: base 70 + escopo 20 + custo 20 + conhecimento 20 = 130. O motivo de criar `expires_at` sem usá-lo não está registrado.
