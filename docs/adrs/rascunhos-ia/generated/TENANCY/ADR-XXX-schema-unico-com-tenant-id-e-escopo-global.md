# ADR-XXX: Multi-tenancy com schema único e escopo global na aplicação

**Status:** Aceita
**Data:** 20-05-2022
**ADRs Relacionados:** Substitui ADR-003 (um schema por clínica, 2019); relacionado a identificação do tenant por requisição e ao escopo aberto sem contexto ativo em jobs e comandos (ADRs potenciais ainda não formalizados)

## Contexto e Problema

Desde 2019 o Horalis isolava cada clínica em um schema próprio do banco (ADR-003). Em abril de 2022, o comando de migration por schema levou cerca de 5h30 para percorrer 380 schemas e deixou parte das clínicas com erro 500 enquanto seus schemas não eram migrados (postmortem de 12-04-2022). Dois problemas precisavam ser resolvidos por qualquer alternativa: o tempo de migration crescia com o número de clínicas (previsão de mais de 600 até o fim de 2023) e o deploy tinha um estado misto, com clínicas em versões diferentes do schema.

A decisão foi tomada na reunião de 20-05-2022, como cumprimento da ação "avaliar o modelo de multi-tenancy" do postmortem. Foram avaliadas quatro alternativas: migrations em paralelo mantendo um schema por clínica, um banco por clínica, schema único com Row Level Security (RLS) e schema único com identificador da clínica em cada tabela e escopo global na aplicação.

A separação física dos dados de saúde era a principal proteção contra exposição entre clínicas. Por isso, a DPO condicionou a unificação à criptografia em nível de campo de CPF e notas clínicas, com um hash com chave para a busca por CPF, em produção e com os dados existentes já convertidos (e-mail de 03-05-2022). A migração para o schema único ocorreu em junho de 2022, e o schema por clínica foi removido do código ao fim do mesmo mês.

## Fatores de Decisão

* Eliminar o estado misto durante o deploy, quando parte das clínicas fica em um schema novo e parte no antigo.
* Fazer o tempo de migration deixar de depender do número de clínicas.
* Manter o isolamento entre clínicas, que passa a depender de código em vez de separação física.
* Respeitar a condição da DPO de criptografar CPF e notas clínicas antes da unificação (LGPD).
* Adotar uma solução que o time atual consiga operar com segurança, o que excluiu RLS naquele momento.
* Reduzir conexões e metadados do banco, como efeito colateral desejável e não como motivo da escolha.

## Opções Consideradas

* Schema único com identificador da clínica em cada tabela e escopo global na aplicação (escolhida)
* Manter um schema por clínica, com migrations em paralelo
* Schema único com Row Level Security do PostgreSQL

Um banco por clínica foi levantado na reunião e descartado de imediato, por piorar a migração e o limite de conexões do RDS; por isso não é detalhado abaixo.

## Resultado da Decisão

Opção escolhida: "Schema único com identificador da clínica em cada tabela e escopo global na aplicação", porque a migration passa a rodar uma única vez, independentemente do número de clínicas, e o deploy deixa de ter estado misto: ou a mudança vale para todas as clínicas, ou para nenhuma.

Todas as tabelas de dados da clínica passam a ter o identificador da clínica. Os models dessas tabelas aplicam um filtro global por clínica e preenchem o identificador na criação. A clínica corrente é mantida em um contexto por requisição. A unificação foi acoplada à criptografia de campo, que foi pré-condição da DPO. O motivo da decisão é o deploy lento e com estado misto. A redução de custo de RDS foi apenas consequência, e a ata registra o pedido explícito de não apresentá-la internamente como motivação.

[NEEDS INPUT: houve outros critérios de decisão além dos registrados na ata de 20-05-2022? Confirmar com Marcos, Thiago e Beatriz, se ainda estiverem na equipe (Rafael saiu em 06/2024).]

## Prós e Contras das Opções

### Schema único com identificador da clínica e escopo global (escolhida)

* Bom, porque a migration roda uma vez só e não há estado misto no deploy.
* Bom, porque reduz conexões e volume de metadados do banco.
* Bom, porque o código necessário é pequeno e o padrão foi reutilizado em tabelas criadas depois (lista de espera, 2023).
* Ruim, porque o isolamento passa a depender de disciplina de código: SQL cru, acesso direto a tabelas e desativação do escopo global escapam do filtro e podem expor dados de outra clínica.
* Ruim, porque dados sensíveis em texto claro passam a pesar mais sem a separação física, o que exigiu a criptografia de campo.

### Schema por clínica com migrations em paralelo

* Bom, porque preserva o isolamento físico entre clínicas.
* Bom, porque um protótipo reduziu a migration de 5h30 para cerca de 50 minutos.
* Ruim, porque o tempo continua crescendo com o número de clínicas.
* Ruim, porque o estado misto continua existindo, ainda que por menos tempo, e o paralelismo é limitado pela carga que o banco suporta em horário de uso.

### Schema único com Row Level Security

* Bom, porque o isolamento seria aplicado pelo próprio banco, como defesa em profundidade.
* Ruim, porque exige definir a clínica na sessão a cada requisição ou transação, e o reaproveitamento de conexões do pool traz o risco de herdar a clínica da requisição anterior.
* Ruim, porque ninguém do time dominava RLS, e erros de política são difíceis de perceber em testes.
* Neutro, porque a ata registra que a ideia pode ser revisitada se o time ganhar experiência.

## Consequências

A decisão está estável desde junho de 2022. Todo model novo de dados de clínica precisa adotar o escopo global e ter o identificador da clínica, e todo desenvolvedor precisa conhecer essa regra. Qualquer uso que contorne o escopo (SQL cru, acesso direto a tabelas, desativação do filtro) é um ponto de risco de vazamento entre clínicas. A mitigação acordada foi uma suíte de testes de isolamento entre clínicas, que existe no repositório. Já a revisão obrigatória desses usos, prometida na ata, não tem evidência no repositório. [NEEDS INPUT: qual é o plano atual para detectar e revisar usos que contornam o escopo global, e RLS segue como opção de defesa em profundidade?]

A migração de dados não copiou os tokens do aplicativo do paciente, e a inferência é que os pacientes do app precisaram entrar novamente após a janela, sem confirmação em outra fonte. O identificador da clínica foi criado como opcional nas tabelas migradas, enquanto a tabela de lista de espera, criada depois, o exige; não há registro do motivo nem migration posterior que torne a restrição obrigatória nas demais. [NEEDS INPUT: o identificador da clínica deveria ser obrigatório em todas as tabelas, e por que foi criado como opcional na migração?]

Há pendências de conformidade sem evidência no repositório: atualização do RIPD e restrição de acesso direto ao banco de produção, ambas recomendadas pela DPO. A documentação de arquitetura do projeto ainda descreve o modelo antigo de schema por clínica e precisa ser atualizada.

## Referências

* app/Tenancy/BelongsToTenant.php
* app/Tenancy/TenantScope.php
* app/Tenancy/TenantContext.php
* database/migrations/2022_06_09_162000_add_tenant_id_to_tabelas_da_clinica.php
* tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php
