# ADR Potencial: Sessão do painel armazenada no Redis (fim da sessão em arquivo)

**Módulo**: AUTH
**Categoria**: Arquitetura / Infraestrutura
**Prioridade**: Considerar (Pontuação: 95)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existente. Relacionado: `autenticacao-por-sessao-no-painel`. Pode ser consolidado nele se a equipe preferir um ADR único (o valor é uma única variável de ambiente), mas o gatilho e a data são distintos.

---

## O Que Foi Identificado

A sessão do painel saiu do disco local (`storage/framework/sessions`, padrão descrito em `docs/ARQUITETURA.md`) para o Redis. Gatilho: em 2021-08-09 a segunda instância da aplicação foi colocada atrás do balanceador; em 2021-08-10 (Slack #arquitetura) usuários eram "deslogados do nada" porque a sessão em arquivo ficava presa a uma instância (o mesmo incidente fez os anexos "sumirem" por estarem em disco local). Rafael Lima: "sessão é só `SESSION_DRIVER=redis`, usa a conexão que já existe" (ElastiCache). A mudança foi aplicada em `4e96d46` (2021-08-17), que também trocou o cache padrão para Redis; em 2021-08-18 as duas instâncias voltaram ao balanceador.

Hoje `config/session.php` tem `'driver' => env('SESSION_DRIVER', 'redis')` e `.env.example` define `SESSION_DRIVER=redis` e `SESSION_LIFETIME=120`. Efeito: o painel depende da disponibilidade do Redis (que também serve fila e cache); uma perda do Redis desloga todos.

## Por Que Isto Pode Merecer um ADR

- **Impacto**: viabiliza múltiplas instâncias (aplicação sem estado local); acopla login à disponibilidade do Redis.
- **Trade-offs**: sem persistência/alta disponibilidade descrita, reinício do Redis encerra sessões.
- **Conhecimento da equipe**: quem for mexer em deploy/escala deve saber que sessão e anexos precisam ser externos.
- **Contexto temporal**: estável desde 2021-08.

## Evidências Encontradas no Código

### Arquivos-chave
- [`config/session.php`](../../../../config/session.php) - linhas 21 e 34
- [`.env.example`](../../../../.env.example) - `SESSION_DRIVER`, `SESSION_LIFETIME`
- [`contexto/slack/arquitetura.md`](../../../../contexto/slack/arquitetura.md) - 2021-08-10 09:12 a 11:13; 2021-08-18 08:50

### Evidência de código
```php
'driver' => env('SESSION_DRIVER', 'redis'),
```

### Análise de Impacto
- Introduzido: `4e96d46` (2021-08-17)
- Afeta: autenticação do painel, deploy com mais de uma instância

### Alternativas (observadas)
Sessão em banco ou cookie não aparecem; a escolha foi imediata pelo Redis já existente.

## Questões a Responder no ADR (se criado)

- Qual o impacto aceitável de perder o Redis (sessões)?
- O Redis de sessão deve ser separado do de fila?

## ADRs Potenciais Relacionados
- [Autenticação por sessão](../../must-document/AUTH/autenticacao-por-sessao-no-painel.md)

## Notas Adicionais
Pontuação: base 70 + escopo 10 + custo 5 + conhecimento 10 = 95. A infraestrutura Redis em si (fila, cache) deve ser tratada nos módulos INFRA/LEMBRETES.
