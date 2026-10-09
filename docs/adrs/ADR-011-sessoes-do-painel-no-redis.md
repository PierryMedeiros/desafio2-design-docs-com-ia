# ADR-011: Sessões do painel no Redis

- **Status:** Accepted
- **Data:** 2021-08-10
- **Decisores:** Rafael Lima (CTO), Thiago Fonseca, Juliana Prado
- **Relações:**
  - depends on [ADR-005: Fila Redis com worker dedicado para os lembretes](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md)
  - relates to [ADR-010: Anexos no S3 com download por URL pré-assinada](ADR-010-anexos-no-s3-com-url-pre-assinada.md)

## Contexto e problema

A sessão do painel era a padrão do Laravel, em arquivo (`storage/framework/sessions`, `docs/ARQUITETURA.md`). Quando a segunda instância da aplicação entrou no balanceador, em 2021-08-09, usuários começaram a ser "deslogados do nada". Juliana identificou a causa: "sessão também tá em arquivo, storage/framework/sessions. trocou de instância, perdeu a sessão" (`contexto/slack/arquitetura.md`, 2021-08-10). A segunda instância foi retirada até resolver.

## Opções consideradas

1. **Sessão no Redis que já existia** (ElastiCache, usado pela fila desde a [ADR-005](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md)).
2. **Manter a sessão em arquivo com uma instância só.** Foi o estado provisório escolhido no dia do incidente.
3. **Needs Input:** as fontes não registram outras opções avaliadas, como sessão no banco (`database`) ou afinidade de sessão (sticky sessions) no balanceador.

## Decisão

Opção 1. Rafael: "e no mesmo movimento a sessão vai pro Redis, que a gente já tem no ElastiCache" e "sessão é só SESSION_DRIVER=redis, usa a conexão que já existe" (Slack, 2021-08-10). O motivo explícito é a sessão sobreviver à troca de instância sem subir nada novo.

O commit `4e96d46` (2021-08-17) troca o driver de sessão e o de cache para `redis`. Em 2021-08-18, as duas instâncias voltaram ao balanceador, e Marcos testou alternando instâncias sem problema.

## Consequências

### Positivas

- A aplicação não guarda mais estado de sessão na instância. Junto com os anexos no S3 ([ADR-010](ADR-010-anexos-no-s3-com-url-pre-assinada.md)), isso permite várias instâncias atrás do balanceador sem afinidade.
- Nenhum serviço novo: reaproveita a conexão Redis existente.

### Negativas

- O Redis passa a ser crítico também para o login. Se ele cair, a equipe é deslogada e não consegue entrar no painel, além de a fila parar.
- Fila, cache e sessão dividem a mesma instância de Redis. **Needs Input:** as fontes não dizem se houve separação de bancos ou instâncias por uso em produção, nem qual é a política de persistência e de eviction do Redis.

## Evidências

- Commits: `4e96d46` (sessões no Redis), `e4cf1d5` (Redis no compose, de 2020).
- Arquivos: `config/session.php`, `config/cache.php`, `.env.example` (`SESSION_DRIVER=redis`, `CACHE_DRIVER=redis`).
- Rastros: `contexto/slack/arquitetura.md` (2021-08-10 a 2021-08-18), `docs/ARQUITETURA.md` (seção "Autenticação", estado de 2019).
