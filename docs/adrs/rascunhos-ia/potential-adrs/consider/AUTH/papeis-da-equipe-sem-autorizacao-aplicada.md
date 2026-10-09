# ADR Potencial: Papéis da equipe (admin, recepção, profissional) sem autorização aplicada

**Módulo**: AUTH
**Categoria**: Segurança
**Prioridade**: Considerar (Pontuação: 95)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existente. Relacionado: `autenticacao-por-sessao-no-painel`.

---

## O Que Foi Identificado

O modelo `User` tem a coluna `papel` (`recepcao` por padrão) e as constantes `PAPEL_ADMIN`, `PAPEL_RECEPCAO` e `PAPEL_PROFISSIONAL`; o `docs/ARQUITETURA.md` (2019) já lista os três papéis. A coluna existe desde o login do painel (`4cef92a`, 2019-04-08). O método `ehAdmin()` foi acrescentado no upgrade do Laravel 10 (`40d1dc9`, 2023-07-11).

Porém, na árvore atual nenhum controller, middleware, policy ou Gate usa o papel: o único uso é exibi-lo no layout (`resources/views/layouts/painel.blade.php`) e criar usuários nos seeders. Todas as rotas do painel exigem só `['auth','tenant']`. Na prática qualquer usuário autenticado da clínica acessa agenda, pacientes (incluindo dados sensíveis), anexos e lista de espera. Não é possível saber, pelo material, se isso é decisão ("por enquanto simples") ou pendência; Camila Rocha avisa em `contexto/LEIA-ME.md` que há casos de "sempre foi assim".

## Por Que Isto Pode Merecer um ADR

- **Impacto**: afeta quem vê dados de saúde; relevante para LGPD (a DPO pediu restringir acessos, ver `contexto/emails/2022-05-03-dpo-criptografia.md`).
- **Trade-offs**: simplicidade versus princípio do menor privilégio.
- **Conhecimento da equipe**: quem adiciona rotas precisa saber que não há autorização por papel.
- **Implicações futuras**: introdução de Policies/Gates exigirá definir matriz de permissões.
- **Contexto temporal**: lacuna estável há mais de 7 anos.

## Evidências Encontradas no Código

### Arquivos-chave
- [`app/Models/User.php`](../../../../app/Models/User.php) - linhas 14-18 e 38-41
- [`routes/web.php`](../../../../routes/web.php) - grupo `['auth','tenant']`
- [`resources/views/layouts/painel.blade.php`](../../../../resources/views/layouts/painel.blade.php) - linha 20
- [`docs/ARQUITETURA.md`](../../../ARQUITETURA.md) - seção "Autenticação"

### Evidência de código
```php
public function ehAdmin(): bool
{
    return $this->papel === self::PAPEL_ADMIN;
}
```
(sem chamadas em `app/`, `routes/` ou `resources/`)

### Análise de Impacto
- Introduzido: `4cef92a` (2019-04-08); `ehAdmin` em `40d1dc9` (2023-07-11)
- Afeta: AUTH, PAINEL

### Alternativas (se observáveis)
Não registradas.

## Questões a Responder no ADR (se criado)

- A autorização por papel foi deliberadamente adiada? Por quê?
- Qual a matriz desejada (ex.: profissional vê só a própria agenda)?

## ADRs Potenciais Relacionados
- [Autenticação por sessão](../../must-document/AUTH/autenticacao-por-sessao-no-painel.md)

## Notas Adicionais
Pontuação: base 70 + escopo 10 + custo 5 + conhecimento 10 = 95. Candidato a ADR de "decisão em aberto": registrar o estado atual e a intenção.
