# ADR-XXX: Hospedagem na AWS com serviços gerenciados e deploy por pipeline

**Status:** Aceito
**Data:** 04-06-2021
**ADRs Relacionados:** ADR-002, ADR-003, ADR-005

## Contexto e Declaração do Problema

Entre 2019 e 2021, o Horalis rodou em uma única VPS contratada e administrada pelo Rafael, que também cuidava dos backups. O banco, o envio de e-mail por SMTP, os lembretes agendados e o worker de fila conviviam no mesmo servidor. O deploy era um script executado por SSH, que colocava a aplicação em manutenção, atualizava o código, instalava dependências, rodava as migrations de todas as clínicas, recarregava o servidor web e reiniciava o worker. Esse script, a configuração do agendador e a do supervisor do worker ficavam versionados no repositório.

A VPS dava sinais de limite: o disco chegou a 85% em maio de 2020, os backups eram manuais e o banco ficava apertado no fim da tarde. Em junho de 2021 a migração para a AWS foi concluída, com banco gerenciado (RDS), Redis gerenciado (ElastiCache), aplicação e worker em execução na nuvem; a VPS foi desligada no fim do mês. A aplicação foi preparada antes, para operar atrás de um balanceador de carga e emitir logs em stderr. Em agosto de 2021 foi adicionada uma segunda instância da aplicação.

A topologia atual (descrita no postmortem de 2022) tem um RDS de instância única, duas instâncias da aplicação atrás de um balanceador, um contêiner de worker, um contêiner de scheduler, ElastiCache e Sentry. O deploy é feito por uma pipeline que atualiza o código nas duas instâncias, executa as migrations e reinicia o worker. Nenhum arquivo de pipeline ou de infraestrutura como código existe no repositório, e as regras de janela de deploy são informais.

[PRECISA DE INFORMAÇÃO: Qual foi o motivo da migração da VPS para a AWS (custo, escala, confiabilidade, backups) e por que banco e Redis gerenciados em vez de instâncias próprias?]

## Fatores de Decisão

- Eliminar o ponto único de falha e a operação manual de servidor e backups concentrada em uma pessoa.
- Permitir mais de uma instância da aplicação, com balanceamento e verificação de saúde.
- Usar serviços gerenciados para banco e Redis, reduzindo trabalho operacional.
- Substituir o deploy por SSH por um processo executado por pipeline.
- Reduzir o risco de mudanças em produção por meio de janelas e congelamentos de deploy.

## Opções Consideradas

1. AWS com serviços gerenciados (RDS, ElastiCache), múltiplas instâncias atrás de balanceador e deploy por pipeline
2. Manter a VPS única com deploy por script via SSH
3. [PRECISA DE INFORMAÇÃO: Que outras alternativas de hospedagem (outro provedor, PaaS, orquestração de contêineres) foram avaliadas, se alguma?]

## Resultado da Decisão

Opção escolhida: "AWS com serviços gerenciados, múltiplas instâncias atrás de balanceador e deploy por pipeline", porque remove o servidor único, entrega redundância da aplicação, backups e criptografia de disco gerenciados no banco e permite escalar horizontalmente. A migração foi precedida de ajustes na aplicação (confiança no proxy, logs em stderr, endpoint de saúde para o balanceador).

O processo de entrega inclui regras operacionais informais, registradas apenas em conversas: sem deploy às sextas, congelamento no fim do ano com apenas hotfix e, após o incidente de abril de 2022, deploys com migration somente após as 20h, com revisão do Rafael e pausa do worker. Estas regras fazem parte da mesma estratégia de entrega e não são tratadas em ADRs separados.

[PRECISA DE INFORMAÇÃO: Qual é a pipeline real (ferramenta, etapas, rollback, quem aprova) e as regras de janela e congelamento continuam vigentes?]

## Prós e Contras das Opções

### AWS com serviços gerenciados e pipeline

- Bom, porque duas instâncias atrás de balanceador dão redundância da aplicação.
- Bom, porque banco e Redis gerenciados tiram da equipe a administração de servidor e backups.
- Bom, porque a pipeline substitui o acesso SSH manual ao servidor.
- Ruim, porque aumenta a dependência da AWS e o custo recorrente.
- Ruim, porque a pipeline e a infraestrutura não estão versionadas no repositório: um novo desenvolvedor não consegue reproduzir a produção.
- Ruim, porque o banco continua em instância única, sem redundância.

### VPS única com deploy por SSH

- Bom, porque o deploy era versionado no repositório e simples de entender.
- Bom, porque o custo era baixo e previsível.
- Ruim, porque era ponto único de falha, com disco no limite e backups manuais.
- Ruim, porque não permitia múltiplas instâncias da aplicação sem retrabalho.

### Outras alternativas

[PRECISA DE INFORMAÇÃO: Prós e contras de qualquer alternativa avaliada, se houve.]

## Consequências

A hospedagem na AWS está estável há mais de cinco anos e sustenta a aplicação, o worker, o scheduler e as integrações. O custo de infraestrutura caiu após a unificação do banco em 2023, mas a redução foi efeito colateral e não o motivo daquela mudança (ver ADR-003).

A principal consequência negativa é a invisibilidade do processo de entrega: a configuração de produção só é conhecida por conversas e pelo postmortem de 2022. A pipeline migra o banco depois de trocar o código, o que contribuiu para o incidente de abril de 2022. A confiança nos cabeçalhos do balanceador foi alterada sem justificativa registrada no upgrade do framework em 2023, e não está confirmado que seja intencional. Toda a equipe precisa conhecer as janelas e congelamentos de deploy, pois só existem como acordo informal.

## Referências

- `routes/web.php:12`
- `app/Http/Middleware/TrustProxies.php`
- `docs/postmortems/2022-04-12-deploy-travado.md`
- `.env.example`
- `contexto/slack/arquitetura.md`
