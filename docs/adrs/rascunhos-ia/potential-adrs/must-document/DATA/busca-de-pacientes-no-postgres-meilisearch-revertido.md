# ADR Potencial: Busca de pacientes mantida no PostgreSQL (POC de Laravel Scout + Meilisearch revertida)

**Módulo**: DATA (consumida por PAINEL)
**Categoria**: Tecnologia / Decisão descartada
**Prioridade**: Documentar obrigatoriamente (Pontuação: 105 = 75 base Step 0 Categoria 1 + 10 + 10 + 10). **Ressalva**: a base de 75 vem do fato de o Meilisearch ter sido um serviço de infraestrutura no compose; como foi removido, o responsável pode rebaixar para "consider" (pontuação sem a base: 30).
**Data da identificação**: 2026-10-09
**Situação no código**: Decisão testada e **revertida**; nada do Meilisearch resta no código

---

## Contexto de ADRs existentes

**POTENCIAL DUPLICADO** (detectado em 2026-10-09, após a identificação):
- `../INFRA/meilisearch-e-scout-poc-revertida.md` (Score 100) cobre a mesma POC revertida. Gerar um só ADR. Este arquivo acrescenta o ângulo do dado pessoal indexado (`nome`, `telefone`, `email`) e a manutenção de `ilike` como busca vigente.

---

## O que foi identificado

Em 2023-05-09 (Slack #geral) as clínicas grandes reclamaram que a busca de pacientes do painel não achava nomes digitados com erro ou sem acento. Marcos Teixeira e Thiago Fonseca propuseram testar **Laravel Scout + Meilisearch**; Rafael Lima autorizou "como teste, duas semanas e a gente decide". Commits: Scout no `Paciente` e `config/scout.php` (`a08461f`, 2023-05-16), Meilisearch no compose (`0494bd3`, 2023-05-16) e busca do painel via Scout com filtro por `tenant_id` (`8629f57`, 2023-05-18).

Em 2023-05-24 o Meilisearch reiniciou de madrugada e atualizações da hora se perderam; o índice da maior conta (Serra Azul) ficou desatualizado até a reindexação (Slack, Thiago). Em 2023-06-06 Marcos concluiu "não compensou": é mais um serviço para manter (índice, reindexação, backup, alerta) e, para o volume atual (a maior clínica tem cerca de 30 mil pacientes), a diferença foi pequena. Três reverts na main no mesmo dia: `a9de541`, `307e564`, `c99efbb`. O container foi desligado em 2023-06-07. A busca voltou a ser `ilike` no Postgres (`81630eb` de 2020-04-15 é a origem dessa busca por nome).

## Por que isso pode merecer um ADR

- **Impacto**: define como a recepção encontra pacientes; deixa explícito que a busca é `ilike '%termo%'` sem índice de trigrama e sem tolerância a erro ou acento.
- **Trade-offs**: simplicidade operacional (um serviço a menos) versus experiência de busca. O problema original das clínicas grandes **não foi resolvido**; a decisão foi "se precisar, melhorar o filtro por nome" (Thiago).
- **Conhecimento do time**: evita que alguém refaça a POC sem conhecer o resultado; pontos de partida concretos para reabrir (volume, `pg_trgm`, `unaccent`).
- **Dado sensível**: o índice enviava `nome`, `telefone` e `email` do paciente ao Meilisearch (`toSearchableArray`), um segundo local com dado pessoal; o CPF não era indexado, já que é criptografado.
- **Contexto temporal**: o ciclo completo durou 3 semanas (2023-05-16 a 2023-06-06).

## Evidências encontradas no código

### Arquivos-chave
- [`app/Http/Controllers/PacienteController.php`](../../../../app/Http/Controllers/PacienteController.php) linha 18: busca atual.
- Histórico: `app/Models/Paciente.php` (`a08461f`), `docker-compose.yml` (`0494bd3`), `PacienteController` e README (`8629f57`).
- [`contexto/slack/geral.md`](../../../../contexto/slack/geral.md) (2023-05-09 a 2023-06-07).

### Evidência de código
```php
// busca atual: app/Http/Controllers/PacienteController.php:18
->where('nome', 'ilike', '%'.$request->input('q').'%')
```

### Análise de impacto (git)
- Introduzido e revertido: 2023-05-16 a 2023-06-06 (6 commits; 3 reverts).
- Temas: "poc", "meilisearch", "revert".

### Alternativas
Scout + Meilisearch (testada e descartada); busca no banco (mantida). Melhorias possíveis citadas: "melhorar o filtro por nome".

## Questões a responder no ADR (se criado)

- Quais números a POC produziu? O Slack diz que Marcos "traria os números", mas o export só traz a conclusão qualitativa.
- Qual o gatilho para reabrir (volume de pacientes, reclamações)?

## ADRs potenciais relacionados
- [PostgreSQL](./postgresql-como-banco-relacional.md)

## Notas adicionais

Não há ADR a criar sobre "adotar Meilisearch"; o registro útil é a decisão de **não** adotar e as razões. O módulo PAINEL pode reclamar o assunto; unificar se for analisado.
