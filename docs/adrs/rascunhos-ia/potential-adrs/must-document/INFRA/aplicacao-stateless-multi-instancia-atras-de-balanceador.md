# ADR Potencial: Aplicação stateless com múltiplas instâncias atrás de balanceador (sessão no Redis, arquivos no S3, logs em stderr, health check)

**Módulo**: INFRA
**Categoria**: Arquitetura
**Prioridade**: Must Document (Score: 120)
**Data de Identificação**: 2026-10-09

---

## Existing ADR Context

ℹ️ **DECISÕES RELACIONADAS**

- `AUTH/sessao-armazenada-no-redis.md`, `ANEXOS/armazenamento-de-anexos-em-s3.md` descrevem partes desta decisão (sessão e arquivos). Este potencial ADR é o princípio guarda-chuva (sem estado local) e inclui balanceador, `/health` e `TrustProxies`.

---

## O que foi identificado

A partir de 2021-05/06 a aplicação passou a rodar em duas instâncias atrás de um balanceador na AWS. O requisito implícito é que nenhuma instância guarde estado local. O Slack (2021-08-10) mostra o primeiro teste: ao subir a segunda instância, anexos em disco local e sessões em arquivo geraram "anexo sumiu" e deslogamentos aleatórios. A resposta foi tornar o app sem estado: sessão e cache no Redis (`4e96d46`, 2021-08-17), anexos no S3 (`1eec607`). O preparo anterior tinha sido `69b2887` (2021-05-11: `TrustProxies` com `'*'` e `LOG_CHANNEL=stderr`) e a rota `/health` veio em `3f8c39d` (2021-06-22). Em 2021-08-18 as duas instâncias voltaram ao balanceador.

Esta é uma decisão de arquitetura (princípio "sem estado local") que não está escrita em lugar algum. Em consequência, qualquer nova funcionalidade que grave em disco local (por exemplo, `storage/app`), cache em arquivo ou estado em memória quebra o ambiente real.

Dois pontos de atenção encontrados:
1. `TrustProxies::$proxies` está hoje `null`; o valor `'*'` de `69b2887` foi removido no commit `40d1dc9` (upgrade para Laravel 10, 2023-07-11), sem explicação. Isso pode afetar IP do cliente e esquema HTTPS atrás do balanceador.
2. Para jobs/comandos, o estado é apenas o do Redis; não há registro de que o worker use disco local.

## Por que isso pode merecer um ADR

- **Impacto**: AUTH, ANEXOS, LEMBRETES, PAINEL (todo o app).
- **Trade-offs**: exige Redis e S3 como dependências críticas; ganha escala horizontal.
- **Complexidade**: sutil; o erro só aparece com mais de uma instância (como em 2021-08).
- **Conhecimento do time**: toda alteração com arquivo, sessão ou cache precisa respeitar a regra.
- **Temporal**: estável há 5 anos.

## Evidências encontradas no código

### Arquivos principais
- [`.env.example`](../../../../../.env.example) - `SESSION_DRIVER=redis`, `CACHE_DRIVER=redis`, `LOG_CHANNEL=stderr`
- [`config/session.php`](../../../../../config/session.php) linha 21
- [`app/Http/Middleware/TrustProxies.php`](../../../../../app/Http/Middleware/TrustProxies.php) - `protected $proxies;` e `HEADER_X_FORWARDED_AWS_ELB`
- [`routes/web.php`](../../../../../routes/web.php) linha 12 - `/health`; [`tests/Feature/HealthTest.php`](../../../../../tests/Feature/HealthTest.php)

### Análise de impacto
- Introduzido: 2021-05-11 (`69b2887`) a 2021-08-17 (`4e96d46`)
- Gatilho: incidente de 2021-08-10 (segunda instância, anexo 404 e deslogamento)
- Temas: balanceador, sessão compartilhada, health check

### Alternativas
- Sticky session no balanceador ou disco compartilhado não aparecem na discussão; a decisão foi direta ("tira a segunda instância por enquanto", depois S3 + Redis).

## Questões a responder no ADR

- A regra "sem estado local" deve ser formalizada?
- Qual o papel do `/health` no balanceador (liveness apenas)? Ele não testa banco/Redis.
- `TrustProxies` em null é intencional?

## ADRs potenciais relacionados
- `redis-fila-cache-e-sessao.md`
- `ANEXOS/armazenamento-de-anexos-em-s3.md` e `ANEXOS/s3-local-de-minio-para-s3mock.md`
- `aws-como-hospedagem-e-pipeline-de-deploy.md`
