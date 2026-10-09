# ADR Potencial: Busca de pacientes com Meilisearch e Laravel Scout (POC revertida)

**Módulo**: INFRA
**Categoria**: Tecnologia (decisão descartada)
**Prioridade**: Must Document (Score: 100)
**Data de Identificação**: 2026-10-09

---

## Existing ADR Context

Nenhum ADR existente. Esta decisão **não está mais no código**; só o histórico e o Slack a registram.

## O que foi identificado

Em 2023-05-09 (#geral), as clínicas grandes reclamaram que a busca de pacientes não tolerava erro de digitação nem falta de acento. Marcos e Thiago propuseram Laravel Scout + Meilisearch como teste de duas semanas (Rafael: "é teste, duas semanas e a gente decide"). Commits: `a08461f` (busca com Scout, 2023-05-16), `0494bd3` (meilisearch no compose, 2023-05-16) e `8629f57` (indexa pacientes, 2023-05-18). Em 2023-05-24 o Meilisearch reiniciou de madrugada e a atualização do índice da maior clínica se perdeu (paciente novo não aparecia). Em 2023-06-06 o resultado do teste foi "não compensou": mais um serviço para manter (índice, reindexação, backup, alerta) e, para o volume atual (a maior clínica tem cerca de 30 mil pacientes), a diferença foi pequena. Foi revertido com três reverts: `a9de541`, `307e564`, `c99efbb` (todos em 2023-06-06); o contêiner foi desligado em 2023-06-07.

O custo operacional do serviço extra foi o argumento decisivo; a busca por nome continua no próprio Postgres. Registrar a decisão de **não** adotar mecanismo de busca separado evita que seja reproposta sem aprender com o teste. Note que a busca por CPF usa `cpf_hash` (`98ff77d`, 2022-05-12), requisito de PRIVACIDADE, e não passaria por um índice externo sem expor dados.

## Por que isso pode merecer um ADR

- **Impacto**: PAINEL (busca) e INFRA (um novo serviço stateful).
- **Trade-offs**: qualidade da busca (tolerância a erros) vs custo operacional de um serviço adicional.
- **Conhecimento do time**: evita retrabalho; critérios de quando reabrir (volume, queixa de clínicas).
- **Futuro**: se a busca por nome ficar lenta ou a dor persistir, a decisão será revisitada.
- **Temporal**: decisão tomada em 4 semanas (2023-05 a 06), válida até hoje.

## Evidências encontradas

- Commits `a08461f`, `0494bd3`, `8629f57` (adoção) e `a9de541`, `307e564`, `c99efbb` (reversão)
- Slack #geral 2023-05-09, 2023-05-18, 2023-05-24, 2023-06-06, 2023-06-07
- Não há código vestigial (Scout e Meilisearch não constam em `composer.json` nem no compose atuais).

### Alternativas
- Manter a busca no banco e "melhorar o filtro por nome" (opção escolhida).
- Meilisearch via Scout (testada e rejeitada).

## Questões a responder no ADR

- Quais números do teste de duas semanas foram apresentados? (não estão no material)
- Qual o gatilho para reavaliar (volume, latência, reclamações)?
- Há solução de busca tolerante a erros dentro do Postgres (`pg_trgm`, `unaccent`)? Não registrado.

## ADRs potenciais relacionados
- `PAINEL`: busca de pacientes por nome e CPF
- `PRIVACIDADE`: `cpf_hash`
- `DATA/postgresql-como-banco-relacional.md`
