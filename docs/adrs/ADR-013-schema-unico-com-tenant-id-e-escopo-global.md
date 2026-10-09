# ADR-013: Schema único com tenant_id e escopo global na aplicação

- **Status:** Accepted
- **Data:** 2022-05-20
- **Decisores:** Rafael Lima (CTO), Juliana Prado, Marcos Teixeira, Thiago Fonseca, Beatriz Nogueira
- **Relações:**
  - supersedes [ADR-003: Um schema por clínica no banco](ADR-003-um-schema-por-clinica-no-banco.md)
  - depends on [ADR-012: Criptografia de campo para CPF e notas clínicas](ADR-012-criptografia-de-campo-para-cpf-e-notas-clinicas.md)
  - relates to [ADR-008: Autenticação da API com tokens do Sanctum](ADR-008-autenticacao-da-api-com-tokens-do-sanctum.md)

## Contexto e problema

Em 2022-04-12, a release 2.14 adicionou a coluna `convenio` em `agendamentos`. O `tenants:migrate` levou cerca de 5h30 para passar pelos 380 schemas de clínica ([ADR-003](ADR-003-um-schema-por-clinica-no-banco.md)). Como o código novo foi ao ar antes, todas as clínicas tiveram erro 500 em algum momento, até o próprio schema ser migrado (`docs/postmortems/2022-04-12-deploy-travado.md`). A primeira ação do postmortem foi "Avaliar o modelo de multi-tenancy".

Na reunião de 2022-05-20, Rafael listou os dois problemas que qualquer opção teria que resolver (`contexto/atas/2022-05-20-reuniao-tenancy.md`):

1. o tempo de migration cresce com o número de clínicas: eram 380, e a previsão comercial era passar de 600 até o fim de 2023;
2. durante o deploy existe um estado misto, com parte das clínicas no schema novo e parte no antigo.

## Opções consideradas

1. **(a) Manter um schema por clínica, com migrations em paralelo por schema.** O protótipo da Juliana, com 8 processos, levaria cerca de 50 minutos.
2. **(b) Um banco por clínica.**
3. **(c) Schema único com Row Level Security (RLS) do Postgres.**
4. **(d) Schema único com coluna `tenant_id` e escopo global na aplicação.**

## Decisão

Opção (d), proposta por Rafael e Marcos. Todas as tabelas de clínica passam a ter `tenant_id`. A trait `BelongsToTenant` aplica um escopo global que filtra por `tenant_id` e preenche o campo na criação. Um middleware identifica a clínica do usuário logado (ou do token, na API) e guarda o tenant da requisição. O `tenants:migrate` deixa de existir, e as migrations voltam a ser um `php artisan migrate` normal.

O motivo, segundo a ata: "A migration passa a rodar uma vez só, independentemente do número de clínicas, e o deploy deixa de ter estado misto".

Por que as outras foram descartadas (ata):

- **(a):** "Só reduz o tempo; não muda a natureza do problema". O estado misto continua, e o paralelismo é limitado pela carga que o banco aguenta. Thiago lembrou que criar índices em paralelo em tabelas grandes derruba o desempenho para todos.
- **(b):** piora a migração (380 bancos com conexões separadas) e complica o custo e a gestão de conexões. O limite de conexões do RDS já era uma preocupação.
- **(c):** conflita com o pool de conexões. A variável de sessão com a clínica poderia vazar de uma requisição ou job para o seguinte. Além disso, ninguém do time dominava RLS. Rafael registrou que a ideia "pode ser revisitada se o time ganhar experiência".

Condição: a unificação só começaria depois que a criptografia de campo pedida pela DPO estivesse em produção, com os dados convertidos ([ADR-012](ADR-012-criptografia-de-campo-para-cpf-e-notas-clinicas.md)).

Implementação:

- `396964c`: `BelongsToTenant`, `TenantScope`, `TenantContext`.
- `e75b0d0`: middleware `IdentificarTenant`.
- `08fab1a`: `tenant_id` nas tabelas da clínica no schema `public`.
- `f60eb60`: comando para copiar os dados dos schemas.
- `f61f605`: testes de isolamento entre clínicas.
- `827b1b7` (2022-06-28): remove o schema por clínica.
- `9356b12`: a API passa a identificar a clínica pelo dono do token.
- `d7a7363`: remove os comandos de migração já executados.

A janela de migração em produção foi concluída num sábado, com "contagens batendo em todas as tabelas" (Slack, 2022-06-27).

## Consequências

### Positivas

- Uma migration roda uma vez para todas as clínicas, e o deploy não tem mais estado misto.
- Menos conexões e menos metadados no banco. Thiago citou na reunião, como efeito colateral, que provavelmente daria para usar uma instância de RDS menor. A ata diz que isso **não foi motivo da escolha**.
- Os testes de isolamento entre clínicas passaram a fazer parte da suíte (`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`).

### Negativas

- O isolamento passa a depender do código. Uma consulta que fuja do escopo global (SQL cru, `DB::table`, `withoutGlobalScopes`) pode trazer dados de outra clínica. A mitigação combinada foram os testes de isolamento e a revisão obrigatória desses usos (ata).
- Sem a separação física, os dados sensíveis em claro passam a pesar mais. Daí a condição da [ADR-012](ADR-012-criptografia-de-campo-para-cpf-e-notas-clinicas.md).
- A reescrita de `lembretes:enfileirar` na unificação passou a comparar horários em UTC, e os lembretes saíram 3 horas adiantados até o hotfix de 2022-11-08 (`81c9949`, `contexto/slack/geral.md`).
- A ata previa que "os jobs carregam o `tenant_id` no payload". O job de lembrete perdeu o `tenant_id` em `827b1b7` e hoje leva só o id do agendamento. Como o `TenantScope` não filtra quando não há tenant no contexto, jobs e comandos dependem de filtro manual por `tenant_id`. O risco está no `docs/HLD.md`.

## Divergência entre fontes sobre o motivo

- **Ata da decisão (`contexto/atas/2022-05-20-reuniao-tenancy.md`):** o motivo é o deploy, ou seja, o tempo de migration e o estado misto. A economia de RDS foi citada como efeito colateral bem-vindo, e Rafael "pediu para não vender a mudança internamente como economia, porque o motivo é o deploy".
- **Slack `#geral`, 2023-10-17 (`contexto/slack/geral.md`):** Helena, montando a apresentação para o conselho, escreve que "a unificação do banco foi pra economizar no RDS". Thiago responde "sim, a conta caiu bastante depois disso".

**Fonte adotada:** a ata. Ela foi escrita no dia da decisão, por um participante (Marcos), e registra a discussão das opções e o motivo declarado por quem decidiu, com a ressalva explícita contra a leitura de economia. O postmortem que originou a avaliação também é sobre o deploy. A mensagem de 2023 vem de quem não participou da reunião, um ano e meio depois. A resposta do Thiago confirma que a conta caiu, o que é compatível com o efeito colateral da ata, mas não mostra que a economia tenha sido o motivo. O `contexto/LEIA-ME.md` também avisa que mensagens de chat são escritas "no calor do momento" e de memória.

## Evidências

- Commits: `396964c`, `e75b0d0`, `08fab1a`, `f60eb60`, `f61f605`, `827b1b7`, `9356b12`, `d7a7363`.
- Arquivos: `app/Tenancy/BelongsToTenant.php`, `app/Tenancy/TenantScope.php`, `app/Tenancy/TenantContext.php`, `app/Http/Middleware/IdentificarTenant.php`, `database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php`, `tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`.
- Rastros: `contexto/atas/2022-05-20-reuniao-tenancy.md`, `docs/postmortems/2022-04-12-deploy-travado.md`, `contexto/slack/arquitetura.md` (2022-04-12, 2022-05-20, 2022-06-27), `contexto/slack/geral.md` (2022-11-08 e 2023-10-17), `contexto/emails/2022-05-03-dpo-criptografia.md`.
