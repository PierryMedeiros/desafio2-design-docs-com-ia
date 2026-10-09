# ADR Potencial: Identificação da clínica na API pelo dono do token (substitui o cabeçalho X-Clinica)

**Módulo**: AUTH (com TENANCY e API)
**Categoria**: Segurança / Arquitetura
**Prioridade**: Deve Documentar (Pontuação: 115)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existente. ⚠️ **POTENCIAL SOBREPOSIÇÃO**: o módulo API identificou o mesmo tema em [identificação do tenant na API (módulo API)](../API/identificacao-do-tenant-na-api-do-cabecalho-para-o-dono-do-token.md) (alta similaridade de palavras-chave). Recomenda-se gerar um único ADR formal, escolhendo um módulo dono. Relacionados (potenciais): `tokens-sanctum-por-dispositivo-em-vez-de-jwt`; ADRs do módulo TENANCY (schema único com `tenant_id`) devem ser lidos em conjunto. Esta é uma decisão que foi **substituída**: o estado antigo não existe mais no código.

---

## O Que Foi Identificado

**Decisão original (2021).** Em 2021-02-18 (Slack #arquitetura), Beatriz perguntou em qual schema ficaria `personal_access_tokens`, já que o paciente loga antes de a aplicação saber a clínica. Rafael Lima: a tabela fica no schema da clínica; o app envia o slug da clínica no login e no cabeçalho `X-Clinica`, e o middleware ajusta o `search_path` antes do Sanctum. Na implementação (`e75f608`, 2021-03-04) a rota usava `tenant.schema` antes de `auth:sanctum`.

**Estado intermediário (2022-06-28).** Na remoção do schema por clínica (`827b1b7`) o middleware `DefinirSchemaTenant` foi apagado e criado `IdentificarTenantPorCabecalho`, que lia `X-Clinica` e definia o `TenantContext` **antes** da autenticação (ordem `tenant.cabecalho`, `auth:sanctum`). Isso significava que o cliente escolhia a clínica e a autenticação ocorria sob o escopo informado por ele.

**Decisão atual (2022-07-12).** `9356b12` ("fix(api): tenant identificado pelo dono do token") removeu `IdentificarTenantPorCabecalho` e passou a usar `['auth:sanctum','tenant']`: o token autentica o paciente e o middleware `IdentificarTenant` (`e75b0d0`) define o tenant a partir do `tenant_id` do dono do token. O cabeçalho `X-Clinica` deixou de existir. No login, o slug `clinica` ainda é enviado no corpo e define o contexto só para localizar o paciente.

O motivo exato da troca não está em ata ou Slack; o commit é do próprio autor da API e vem na sequência da ata de 2022-05-20 ("um middleware identifica a clínica do usuário logado (ou do token, na API)"). A inferência é que o cabeçalho permitia a um cliente trocar de clínica sob um token válido. Tratar como hipótese a confirmar.

## Por Que Isto Pode Merecer um ADR

- **Impacto**: é a fronteira de isolamento entre clínicas na API (LGPD).
- **Trade-offs**: derivar o tenant do token é mais seguro que confiar no cliente, mas o `TenantScope` não filtra quando o contexto está inativo (jobs/comandos).
- **Complexidade**: média; a ordem dos middlewares importa.
- **Conhecimento da equipe**: quem escreve rotas novas deve usar `['auth:sanctum','tenant']`.
- **Implicações futuras**: se o app for atualizado para um contrato novo, pode-se remover o slug do login (usar domínio/subdomínio por clínica).
- **Contexto temporal**: a versão final tem mais de 4 anos; o desenho original durou ~16 meses.

## Evidências Encontradas no Código

### Arquivos-chave
- [`routes/api.php`](../../../../routes/api.php) - linha 11
- [`app/Http/Middleware/IdentificarTenant.php`](../../../../app/Http/Middleware/IdentificarTenant.php)
- [`app/Http/Controllers/Api/V1/AuthController.php`](../../../../app/Http/Controllers/Api/V1/AuthController.php) - linhas 24-28
- [`tests/Feature/Tenancy/IdentificarTenantTest.php`](../../../../tests/Feature/Tenancy/IdentificarTenantTest.php)
- [`contexto/atas/2022-05-20-reuniao-tenancy.md`](../../../../contexto/atas/2022-05-20-reuniao-tenancy.md)

### Evidência de código (histórico, removido em `9356b12`)
```php
$tenant = Tenant::where('slug', $request->header('X-Clinica'))->firstOrFail();
$this->contexto->definir($tenant);
```

### Análise de Impacto
- Introduzido: 2021-03-04 (`e75f608`); trocado em 2022-06-28 (`827b1b7`) e 2022-07-12 (`9356b12`)
- Afeta: todas as rotas autenticadas da API

### Alternativas (se observáveis)
Cabeçalho `X-Clinica` (descartado). Domínio por clínica não aparece no material.

## Questões a Responder no ADR (se criado)

- Por que o cabeçalho foi abandonado? Houve incidente ou revisão de segurança?
- O slug no login continua necessário? E se dois pacientes de clínicas diferentes tiverem o mesmo e-mail?
- O app 3.0 ainda envia `X-Clinica`?

## ADRs Potenciais Relacionados
- [Tokens Sanctum](./tokens-sanctum-por-dispositivo-em-vez-de-jwt.md)
- [Credenciais do paciente](../../consider/AUTH/credenciais-proprias-do-paciente-para-o-app.md)

## Notas Adicionais
Pontuação: base 70 (autenticação/identidade como domínio crítico) + escopo 15 + custo 15 + conhecimento 15 = 115. Registrar como decisão **substituída**; o cabeçalho não existe mais no código.
