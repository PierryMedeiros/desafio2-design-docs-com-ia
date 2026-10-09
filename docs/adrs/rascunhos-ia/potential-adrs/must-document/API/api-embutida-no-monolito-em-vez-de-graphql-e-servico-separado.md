# ADR Potencial: API embutida no monólito Laravel, em vez de GraphQL e serviço separado (plano de 2019 abandonado)

**Módulo**: API
**Categoria**: Arquitetura (decisão superada/revertida)
**Prioridade**: Must Document (Score: 130)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe. Relacionado: [API REST versionada na URL](./api-rest-versionada-na-url-no-monolito.md).

---

## O Que Foi Identificado

Em 2019 a intenção documentada era outra. A ata do kickoff (`contexto/atas/2019-04-02-kickoff-tecnico.md`, 2019-04-02) deixou o app do paciente fora do MVP e descartou "back-end em Node + SPA em React", registrando que Rafael Lima achava que "pode fazer sentido rever no futuro se houver app ou front mais rico". O `docs/ARQUITETURA.md` (criado em `fd9a487`, 2019-06-14, atualizado em `dd800dc`, 2019-07-03) afirma que "pacientes não acessam o sistema" e lista como próximos passos um "app do paciente em React Native, consumindo uma API GraphQL" e "separar a agenda num microsserviço em 2020".

Nada disso aconteceu. Em 2021-02-17 (Slack #arquitetura), ao fechar o escopo do app, Beatriz Nogueira perguntou "hoje tudo é Blade, né?" e Rafael respondeu que "a api mora no mesmo monolito, em routes/api.php", sem discussão de GraphQL ou de serviço separado. O código resultante é um conjunto de controllers `Api\V1` que reutilizam models, `TenantContext` e, desde 2025, o serviço `App\Agenda\Disponibilidade` compartilhado com o painel (`e3b2252` em 2025-01-21 e `8cd4f7d` em 2025-02-18). A agenda nunca foi extraída em microsserviço.

É uma decisão em grande parte implícita: a GraphQL é abandonada sem registro de por quê (o contexto não diz que foi avaliada e rejeitada; apenas deixa de ser mencionada). Isso deve ficar explícito no ADR como "motivo não registrado". O documento de arquitetura continua afirmando o contrário, o que induz a erro.

## Por Que Isto Pode Merecer um ADR

- **Impacto**: determina onde mora a lógica de agendamento (um só código para painel e app) e que deploy, banco e tenancy são compartilhados.
- **Trade-offs**: simplicidade operacional e consistência de regras contra acoplamento de ciclo de deploy entre painel e API e impossibilidade de escalar a API de forma independente.
- **Complexidade**: baixa hoje; evidências reais do acoplamento: o incidente de 2022-04-12 derrubou painel e app juntos (`docs/postmortems/2022-04-12-deploy-travado.md`, "erro 500 em `GET /api/v1/agendamentos`").
- **Conhecimento do time**: qualquer pessoa nova que leia `docs/ARQUITETURA.md` vai procurar GraphQL e microsserviço que não existem.
- **Implicações futuras**: um novo canal (ex.: app web, integrações de terceiros) precisaria decidir entre estender a v1 ou revisitar o plano.
- **Contexto temporal**: o plano durou de 2019-06 a 2021-02; a realidade atual é estável há 5 anos.

## Evidências Encontradas no Código

### Arquivos-chave
- [`docs/ARQUITETURA.md`](../../../../../docs/ARQUITETURA.md) - linhas 9 e 68-70 (GraphQL, microsserviço, "pacientes não acessam o sistema").
- [`routes/api.php`](../../../../../routes/api.php) - API dentro do monólito.
- [`app/Agenda/Disponibilidade.php`](../../../../../app/Agenda/Disponibilidade.php) - serviço de domínio usado por painel e API.
- `contexto/slack/arquitetura.md` (2021-02-17) e `contexto/atas/2019-04-02-kickoff-tecnico.md`.

### Evidência de Código
```php
// app/Http/Controllers/Api/V1/AgendamentoController.php (reutiliza o serviço do painel)
public function store(Request $request, Disponibilidade $disponibilidade): JsonResponse
```
```text
# docs/ARQUITETURA.md (estado de 2019, desatualizado)
- App do paciente em React Native, consumindo uma API GraphQL.
- Separar a agenda num microsserviço em 2020, quando o número de clínicas justificar.
```

### Análise de Impacto
- Plano documentado: 2019-06-14 (`fd9a487`) e 2019-07-03 (`dd800dc`); nunca mais atualizado (último commit do doc).
- Realidade implantada: 2021-03 (`e75f608`).
- Consolidação de lógica entre painel e API: 2025-01/02 (`e3b2252`, `8cd4f7d`); antes disso, correções de horários livres precisaram ser feitas na API separadamente (`0ff7246` bloqueios, 2022-11; `70931e1` cancelados, 2023-02; `a00525b` fuso, 2022-03), indício de duplicação de regra.
- Afeta: toda a aplicação (um único deploy).

### Alternativas (observáveis)
- GraphQL e microsserviço de agenda: previstos em 2019, não implementados.
- Node + React: descartado em 2019 por falta de experiência e prazo.

## Questões a Responder no ADR (se criado)

- Por que GraphQL deixou de ser considerado? Houve avaliação ou foi omissão?
- Quais condições fariam reabrir a separação (escala, times, deploy)?
- Como manter o `ARQUITETURA.md` coerente (substituir por ADRs)?

## ADRs Potenciais Relacionados
- [API REST versionada na URL](./api-rest-versionada-na-url-no-monolito.md)
- Módulos PAINEL e AGENDA (serviço `Disponibilidade` compartilhado).

## Notas Adicionais
- O motivo da troca de GraphQL por REST não está em nenhuma fonte; marcar como lacuna.
- O app mobile não está no repositório, então não se verifica quais outros clientes consomem a API.
