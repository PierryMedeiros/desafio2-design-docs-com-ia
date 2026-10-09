# ADR-007: API REST versionada na URL, dentro do monólito

- **Status:** Accepted
- **Data:** 2021-02-17
- **Decisores:** Rafael Lima (CTO), Beatriz Nogueira, Marcos Teixeira
- **Relações:**
  - depends on [ADR-001: Monólito Laravel com telas renderizadas em Blade](ADR-001-monolito-laravel-com-telas-em-blade.md)

## Contexto e problema

Em fevereiro de 2021, Rafael e Beatriz fecharam o escopo da primeira versão do app do paciente: ver horários livres, marcar, ver os agendamentos e cancelar. Até ali tudo era Blade, e o app precisava de uma API (`contexto/slack/arquitetura.md`, 2021-02-17).

Havia uma restrição própria de app de loja: não dá para forçar atualização, e "tem paciente que vai ficar meses com a versão velha instalada" (Beatriz).

## Opções consideradas

Sobre onde a API mora:

1. **No mesmo monólito, em `routes/api.php`.**
2. **Needs Input:** o Slack não registra outra opção avaliada em 2021. O `docs/ARQUITETURA.md` de 2019 previa, nos próximos passos, um "App do paciente em React Native, consumindo uma API GraphQL" e um microsserviço de agenda. Nenhuma das duas ideias aparece na discussão de 2021.

Sobre o estilo e o versionamento:

1. **REST com a versão na URL** (`/api/v1/...`), proposta da Beatriz.
2. **Versão no header**, levantada pelo Marcos ("não seria mais 'correto'?").

## Decisão

API REST dentro do monólito, com a versão na URL: `/api/v1`. Rafael: "a api mora no mesmo monolito, em routes/api.php". E sobre o versionamento: "vai de URL. simples".

Motivos registrados:

- **Versão na URL em vez do header:** "na URL é mais fácil de enxergar no log e de rotear. e no app é só a base url" (Beatriz).
- **Regra de compatibilidade:** o contrato da v1 não quebra. "se mudar muito, vira /api/v2 e a v1 continua viva até o pessoal atualizar" (Beatriz).

A autenticação da API é uma decisão separada ([ADR-008](ADR-008-autenticacao-da-api-com-tokens-do-sanctum.md)).

Implementação:

- `c1f87bc`: horários livres.
- `5d1b376`: agendamentos do paciente.
- `b267361`: cancelamento.
- `4f09b9c`: testes da v1.

A `/api/v1` entrou em produção em 2021-03-24. As mudanças seguintes foram aditivas: paginação (`1cd87ff`), dados de profissional e serviço (`5da94bb`) e reagendamento (`09c6423`).

## Consequências

### Positivas

- A API reaproveita models, regras de agenda e tenancy do monólito, e o mesmo deploy cobre painel e API.
- Desde 2025 a regra de horários livres é um serviço único (`app/Agenda/Disponibilidade.php`) usado pelo painel e pela API (`e3b2252`, `8cd4f7d`).
- A regra de compatibilidade foi cumprida: o app 3.0 continuou usando a `/api/v1`, "não precisou de v2" (Slack, 2023-08-01). O upgrade para Laravel 10 "não mudou nada pra fora" (2023-07-05).

### Negativas

- A API compartilha o ciclo de deploy e as falhas do monólito. No incidente de 2022-04-12, o `GET /api/v1/agendamentos` e a marcação deram erro 500 junto com o painel (`docs/postmortems/2022-04-12-deploy-travado.md`).
- Até 2025 as regras de horário livre ficavam duplicadas entre painel e API e tiveram que ser corrigidas na API separadamente: `a00525b` (fuso), `0ff7246` (bloqueios), `70931e1` (cancelados).
- O contrato ficou num workspace do Postman, fora do repositório. O link do README já quebrou uma vez (Slack, 2022-01-19). Não há especificação OpenAPI versionada.

## Divergência entre fontes

O `docs/ARQUITETURA.md` (2019) planejava uma API GraphQL para o app. A discussão de 2021 decidiu REST sem mencionar GraphQL. Adotei o Slack de 2021 e o código, que registram a decisão efetivamente tomada, e tratei o documento de 2019 como intenção anterior que não se concretizou.

**Needs Input:** por que o plano de GraphQL de 2019 foi abandonado, e se ele chegou a ser avaliado em 2021?

## Evidências

- Commits: `c1f87bc`, `5d1b376`, `b267361`, `4f09b9c`, `1cd87ff`, `5da94bb`, `09c6423`, `8cd4f7d`.
- Arquivos: `routes/api.php`, `app/Http/Controllers/Api/V1/AgendamentoController.php`, `app/Http/Controllers/Api/V1/HorarioController.php`, `app/Http/Resources/AgendamentoResource.php`.
- Rastros: `contexto/slack/arquitetura.md` (2021-02-17, 2021-03-24, 2022-01-19, 2023-07-05, 2023-08-01), `docs/ARQUITETURA.md` (próximos passos).
