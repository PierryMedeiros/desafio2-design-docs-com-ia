# ADR Potencial: Autenticação por sessão (e-mail e senha) para a equipe no painel

**Módulo**: AUTH
**Categoria**: Segurança / Arquitetura
**Prioridade**: Deve Documentar (Pontuação: 130)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existente (`docs/adrs/generated/` não existe). Relacionados (potenciais): `sessao-armazenada-no-redis`, `identificacao-do-tenant-pelo-dono-do-token`, `papeis-da-equipe-sem-autorizacao-aplicada`.

---

## O Que Foi Identificado

O painel web (Blade) autentica a equipe da clínica (admin, recepção, profissional) com o guard `web` padrão do Laravel: `Auth::attempt` com e-mail e senha, provider Eloquent sobre `App\Models\User`, regeneração da sessão no login e invalidação no logout, cookie de sessão `http_only` e `same_site=lax`, vida de 120 minutos e opção "lembrar". As rotas ficam em `routes/web.php` sob `auth` + `tenant`; a clínica é descoberta pelo `tenant_id` do usuário logado.

O login foi criado em 2019-04-08 (`4cef92a`, "login do painel"), uma semana após o commit inicial em Laravel 5.8 (`c5c258b`), junto com a decisão de renderizar tudo em Blade (`92b00f0`, "remove frontend do skeleton, vamos de blade puro"). Não há SPA nem login federado (SSO), 2FA ou recuperação de senha própria nas rotas atuais. O hash da senha passou a usar o cast `hashed` no upgrade para Laravel 10 (`40d1dc9`, 2023-07).

A decisão tem uma história de mudança de contexto: o `docs/ARQUITETURA.md` (2019) diz que a tabela `users` ficava no schema `public` e a clínica era resolvida via `search_path`. Com a unificação para schema único (`08fab1a`, 2022-06-09; `827b1b7`, 2022-06-28) o usuário passou a ser filtrado por `tenant_id` (`396964c`, "escopo global de tenant nos models"), e o middleware `tenant` (`e75b0d0`, 2022-06-08) lê `$request->user()->tenant_id`.

## Por Que Isto Pode Merecer um ADR

- **Impacto**: toda rota do painel depende dele; a identidade do usuário é também a fonte do isolamento entre clínicas.
- **Trade-offs**: sessão de servidor simplifica revogação e dispensa API para o painel, mas exige sessão compartilhada entre instâncias (ver ADR de Redis). O e-mail de `users` é `unique` global (`2014_10_12_000000_create_users_table.php`), então a mesma pessoa não pode ter conta em duas clínicas com o mesmo e-mail; ao mesmo tempo, `Auth::attempt` roda sem contexto de tenant (o `TenantScope` não filtra nesse caso).
- **Complexidade**: baixa no código, alta nas implicações (LGPD, multi-clínica).
- **Conhecimento da equipe**: todo desenvolvedor mexendo em rotas do painel precisa saber.
- **Implicações futuras**: SSO, 2FA, recuperação de senha, throttling de login (o alias `throttle` existe em `app/Http/Kernel.php`, mas não é usado em `routes/web.php`).
- **Contexto temporal**: estável há mais de 7 anos.

## Evidências Encontradas no Código

### Arquivos-chave
- [`app/Http/Controllers/Auth/LoginController.php`](../../../../app/Http/Controllers/Auth/LoginController.php) - linhas 26-32 e 41-42
- [`routes/web.php`](../../../../routes/web.php) - grupos `guest`, `auth` e `['auth','tenant']`
- [`config/auth.php`](../../../../config/auth.php) - guard `web`, provider Eloquent
- [`config/session.php`](../../../../config/session.php) - linha 34 (`lifetime` 120), `http_only`, `same_site`
- [`app/Models/User.php`](../../../../app/Models/User.php) - `BelongsToTenant`, papéis, cast `hashed`
- [`app/Http/Middleware/IdentificarTenant.php`](../../../../app/Http/Middleware/IdentificarTenant.php)
- [`docs/ARQUITETURA.md`](../../../ARQUITETURA.md) - seção "Autenticação" (estado de 2019)

### Evidência de código
```php
// app/Http/Controllers/Auth/LoginController.php
if (! Auth::attempt($credenciais, $request->boolean('lembrar'))) { ... }
$request->session()->regenerate();
```

### Análise de Impacto
- Introduzido: 2019-04-08 (`4cef92a`)
- Mudanças relevantes: upgrades de Laravel (`5f57d55`, `2069974`, `9cae2ef`, `40d1dc9`), `tenant_id` no usuário e escopo global (`396964c`), sessão no Redis (`4e96d46`)
- Testes: `tests/Feature/Painel/LoginTest.php`
- Afeta: todo o painel (módulos PAINEL, TENANCY, ANEXOS)

### Alternativas (se observáveis)
Não há discussão registrada sobre alternativas ao login por sessão. O material de contexto (`contexto/`) não contém ata sobre o assunto; o que se sabe vem do commit e do `ARQUITETURA.md`. Tratar o motivo como inferido ("é o padrão do Laravel").

## Questões a Responder no ADR (se criado)

- Por que sessão de servidor e não token também no painel?
- Por que e-mail globalmente único em `users`? Isso é uma restrição desejada?
- Há plano para 2FA, recuperação de senha, bloqueio por tentativas?

## ADRs Potenciais Relacionados
- [Sessão no Redis](../../consider/AUTH/sessao-armazenada-no-redis.md)
- [Papéis sem autorização aplicada](../../consider/AUTH/papeis-da-equipe-sem-autorizacao-aplicada.md)
- [Identificação do tenant pelo dono do token](./identificacao-do-tenant-pelo-dono-do-token.md)

## Notas Adicionais
Pontuação: base 70 (autenticação como infraestrutura crítica do domínio) + escopo 20 + custo de mudança 20 + conhecimento 20 = 130. Motivação original não documentada.
