# Arqueologia de Decisões: Reconstruindo o Porquê de um Legado com IA

A Horalis é um SaaS de agendamento para clínicas que está no ar desde 2019. Nasceu como um MVP de três meses para três clínicas parceiras e hoje atende centenas, com painel para a recepção, uma API para o app do paciente e lembretes automáticos de consulta. Nesses seis anos e meio, o sistema passou por decisões grandes, algumas desfeitas anos depois. Nenhuma foi registrada como decisão.

O CTO fundador e a primeira desenvolvedora, que tomaram boa parte dessas decisões, já saíram da empresa. Ficaram o código, o histórico do git, um documento de arquitetura escrito em 2019 e o que a tech lead, Camila Rocha, conseguiu exportar de atas, e-mails e canais do Slack.

O assunto virou urgente quando o time começou a usar agentes de IA no código. Cada nova sessão é um funcionário novo que não sabe nada do projeto, e, sem contexto confiável, os agentes tratam como vigente o que já mudou e propõem mudanças sem saber por que o sistema é como é. A Camila quer resolver isso pela raiz e passou a missão para você.

O desafio cabe em uma frase: reconstruir, a partir do código, do histórico do git e de rastros incompletos, as decisões arquiteturais da Horalis e o retrato da arquitetura de hoje, sem inventar nada.

A tensão central está nessa última parte. O histórico prova o que mudou e quando, mas não diz por quê. Os rastros dizem por quê, mas são incompletos e nem sempre concordam entre si. E a IA, sua principal ferramenta aqui, preenche lacunas com o que soa razoável. Separar o que está provado do que só parece plausível é o trabalho.

## Objetivo

Ao final, o seu fork do repositório base precisa ter:

- as ADRs das decisões arquiteturais da Horalis, de 2019 a 2025, com ciclo de vida e evidências;
- um índice dessas ADRs com a linha do tempo, o grafo de relações e a lista do que você avaliou e concluiu que não pedia ADR;
- o HLD do sistema como ele está hoje;
- os diagramas C4 dos níveis 1 a 3, também do sistema de hoje;
- um README contando como você conduziu a IA nesse trabalho.

## O repositório base

O repositório base é REPO-A-DEFINIR. Faça um fork público e clone o fork completo: o histórico de commits é material de trabalho, não detalhe.

O que você recebe:

- O código da Horalis: um monólito em PHP 8.2 com Laravel 10, com painel em Blade e uma API versionada para o app do paciente. O app em si não está no repositório.
- O `docker-compose.yml` do ambiente de desenvolvimento.
- O histórico do git, de abril de 2019 a novembro de 2025, com commits de seis pessoas.
- `docs/ARQUITETURA.md`, o documento de arquitetura escrito pelo CTO em 2019.
- `docs/postmortems/`, com o postmortem de um incidente de 2022.
- `contexto/`, com o que a tech lead exportou em 2026: duas atas, um e-mail e o histórico de dois canais do Slack. O `LEIA-ME.md` dessa pasta explica o que entrou e o que se perdeu; comece por ele.

Neste enunciado, rastros são os arquivos de `contexto/`, o `docs/ARQUITETURA.md` e o `docs/postmortems/`. Código, histórico e rastros são as suas fontes.

Rodar a aplicação não é requisito, e nenhum critério depende disso, mas ajuda a confirmar o que o código diz. Com Docker e Docker Compose:

```
cp .env.example .env
docker compose up -d --build
```

Na primeira subida, o contêiner da aplicação roda as migrations e o seed sozinho. O painel abre em http://localhost:8080, com o usuário `recepcao@bem-estar.test` e a senha `password`.

## IA e ferramentas

A escolha de ferramentas é livre: Claude Code, Cursor, Copilot, Gemini, ChatGPT ou qualquer combinação. Os plugins de ADRs e de diagramas que o professor usou no curso estão em https://github.com/devfullcycle/claude-mkt-place e são um bom ponto de partida. O seu papel é o de quem conduz: decidir o que investigar, escrever os prompts e conferir cada afirmação da IA contra as fontes antes de aceitá-la.

## Requisitos

### 1. ADRs reconstruídas

Conceitos do curso: estrutura clássica e MADR, status, metadados de relação e boas práticas (capítulo 5, aulas 2 a 5); quando usar, quando é opcional e quando não usar ADR, com a regra dos 3 Es (capítulo 5, aulas 6 a 8); mapeamento de legado, Needs Input e linkagem (capítulo 5, aulas 9 a 12).

Encontre as decisões arquiteturais que moldaram a Horalis e registre cada uma como ADR, no formato MADR, diretamente em `docs/adrs/`. Ao final, isto precisa ser verdade:

- Cada ADR trata de uma única decisão e segue o padrão de nome `ADR-NNN-titulo-em-kebab-case.md`, com numeração a partir de 001, sem buracos, na ordem cronológica das decisões.
- Os metadados trazem status e data (AAAA-MM-DD), com os status do curso: Proposed, Accepted, Rejected, Deprecated ou Superseded. Quando houver relação com outra ADR, ela aparece com os termos supersedes, superseded by, amends, amended by, depends on ou relates to, e com link.
- A data é a da decisão, sustentada por um commit ou rastro citado na própria ADR.
- O corpo tem contexto, opções consideradas, decisão e consequências positivas e negativas, com títulos em português ou com os nomes de seção do MADR.
- Cada ADR cita as evidências em que se apoia: pelo menos um commit (hash) e um arquivo do repositório e, quando houver, os rastros de onde saiu o porquê.
- Motivos, alternativas e consequências vêm das fontes. O que as fontes não respondem fica marcado como Needs Input, dizendo o que falta saber.
- Quando as fontes divergem sobre o motivo de uma decisão, a ADR registra a divergência e diz qual fonte adotou e por quê.
- O conjunto tem entre 8 e 16 ADRs.

Artefatos intermediários, como mapeamentos e rascunhos, podem ficar dentro de `docs/adrs/`. Só os arquivos no padrão de nome das ADRs, diretamente na pasta, são avaliados como ADR.

### 2. Índice e linha do tempo

Conceitos do curso: linkagem e linha do tempo das ADRs (capítulo 5, aula 12), regra dos 3 Es (capítulo 5, aula 8) e diagramas Mermaid (capítulo 4, aulas 9 a 13).

Crie `docs/adrs/README.md` como porta de entrada das decisões. Ele traz a linha do tempo de todas as ADRs, um diagrama Mermaid com as relações entre elas e a lista dos candidatos que você avaliou e concluiu que não pediam ADR. É nessa lista que o seu julgamento aparece: cada item explica, pela regra dos 3 Es (a decisão precisa ser estrutural, evidente e estável), por que ficou de fora.

### 3. HLD do estado atual

Conceitos do curso: High Level Design, seções típicas e exemplo (capítulo 3, aulas 3 a 5).

Escreva `docs/HLD.md` com o retrato da Horalis como ela está no HEAD do repositório, conferido no código. Ele cobre as seções que o curso apresenta para um HLD: objetivo técnico, arquitetura geral, componentes, fluxo de requisição, modelo de dados, interfaces públicas, escalabilidade, segurança, observabilidade e riscos. Quando o HLD descrever uma escolha que tem ADR vigente, ele linka essa ADR.

### 4. C4 do estado atual

Conceitos do curso: modelo C4 nos níveis C1, C2 e C3 e geração com PlantUML (capítulo 4, aulas 2 a 8).

Crie em `docs/c4/` os diagramas dos níveis 1 (contexto), 2 (containers) e 3 (componentes do container da aplicação), um arquivo PlantUML por nível, com a biblioteca C4 do PlantUML e o nível no nome do arquivo (ex.: `horalis-c1.puml`). No C2, cada relação diz a tecnologia ou o protocolo usado. Os três retratam o mesmo sistema do HLD: o de hoje.

### 5. README do processo

Conceitos do curso: documentação como contexto e ativo na era da IA (capítulo 2, aula 1) e o uso de prompts e agentes ao longo da disciplina.

Substitua o `README.md` da raiz, que hoje contém este enunciado, pela história do seu trabalho: quais ferramentas de IA você usou e para quê, como organizou a investigação, os prompts que escreveu ou adaptou, os momentos em que a IA errou e você percebeu, e como navegar a entrega. Você pode manter um link para o enunciado original.

## Restrições

- Todo o seu trabalho fica em `docs/adrs/`, `docs/HLD.md`, `docs/c4/` e `README.md`. O código, a configuração, os testes e os rastros ficam como estão. Se uma fonte parecer errada ou incompleta, registre isso na ADR ou no HLD em vez de corrigi-la.
- O histórico do base fica intacto: nada de rebase, squash ou force push sobre ele. Os seus commits entram por cima, e todo hash que você citar precisa existir no seu fork.
- As ADRs registram o que foi decidido e por quê. Não registram decisões novas nem a sua opinião sobre as antigas. Riscos e problemas que você enxergar vão para a seção de riscos do HLD.

## Fora de escopo

- PRD, RFC, FDD e LLD.
- O nível 4 do C4 (código).
- Engineering guidelines.
- Qualquer mecanismo para manter a documentação atualizada automaticamente.
- Corrigir bugs ou refatorar o código, mesmo que você encontre problemas.
- O funcionamento interno do app mobile, que só existe aqui como cliente da API.

## Critérios de aceite

Todos são obrigatórios. Parte deles é conferida contra uma lista interna do avaliador, montada a partir das fontes. Essa lista não é divulgada, porque encontrar o que ela contém é o desafio.

### ADRs

☐ `docs/adrs/` tem, diretamente na pasta, entre 8 e 16 arquivos no padrão `ADR-NNN-titulo-em-kebab-case.md`, numerados a partir de 001 sem buracos, e a numeração segue a ordem das datas registradas (decisões do mesmo dia podem vir em qualquer ordem).

☐ Cada ADR tem status e data (AAAA-MM-DD) nos metadados, com os status do curso, e as seções de contexto, opções consideradas, decisão e consequências positivas e negativas.

☐ Cada ADR trata de uma única decisão. Uma ADR que junta duas decisões da lista do avaliador conta como uma só na cobertura.

☐ Cada ADR cita pelo menos um hash de commit e um caminho de arquivo. Todo hash citado existe no fork (`git cat-file -e <hash>` termina sem erro), e todo caminho citado existe no HEAD ou em algum commit da `main` (`git log main --oneline -- <caminho>` não volta vazio).

☐ A data de cada ADR coincide com a data de um commit ou de um rastro citado nela e cai na janela da decisão na lista do avaliador, que vai do primeiro registro dela nas fontes (discussão ou commit, o que vier antes) até o último commit que a implementa.

☐ O conjunto cobre pelo menos 8 das decisões arquiteturais da lista do avaliador.

☐ Nenhuma ADR, em nenhum status, registra um caso que os critérios do curso apontam como não sendo matéria de ADR. A lista do avaliador inclui os casos desse tipo presentes no histórico.

☐ As relações de substituição, emenda e dependência que as fontes sustentam estão registradas, com link. Substituição e emenda aparecem nas duas ADRs de cada par, e a ADR substituída tem status Superseded.

☐ Onde as fontes não respondem o porquê ou as alternativas de uma decisão, a ADR traz a marcação Needs Input em vez de uma resposta. A lista do avaliador inclui casos assim.

☐ Onde as fontes divergem sobre o motivo de uma decisão, a ADR registra a divergência e justifica a fonte que adotou.

### Índice (`docs/adrs/README.md`)

☐ Lista todas as ADRs em ordem, com número, título, status, data e link.

☐ Tem um diagrama Mermaid que renderiza no GitHub. Toda relação entre ADRs declarada nos metadados aparece no diagrama (uma seta por par basta), e nenhuma seta do diagrama deixa de existir nos metadados.

☐ Lista pelo menos 4 candidatos avaliados que não viraram ADR, cada um com evidência (hash ou rastro) e a justificativa pela regra dos 3 Es.

### HLD (`docs/HLD.md`)

☐ Cobre objetivo técnico, arquitetura geral, componentes, fluxo de requisição, modelo de dados, interfaces públicas, escalabilidade, segurança, observabilidade e riscos.

☐ Retrata o HEAD: nenhum mecanismo, componente ou tecnologia que deixou de existir aparece como atual, e nenhum container que existe hoje fica de fora.

☐ Linka as ADRs vigentes das escolhas que descreve.

### C4 (`docs/c4/`)

☐ Tem um arquivo PlantUML para cada nível (1, 2 e 3), com o nível no nome, usando a biblioteca C4 do PlantUML (`!include <C4/...>` ou a inclusão equivalente do C4-PlantUML).

☐ Os três compilam: `java -jar plantuml.jar -checkonly "docs/c4/*.puml"` termina com código 0.

☐ O C1 mostra as pessoas e os sistemas externos com que a Horalis se relaciona hoje. O C2 mostra todos os containers que existem hoje, com a tecnologia de cada relação. O C3 mostra os componentes do container da aplicação. Nenhum dos três mostra o que deixou de existir.

### README

☐ Lista as ferramentas de IA usadas e o papel de cada uma.

☐ Descreve como você organizou o trabalho: em que ordem investigou as fontes e produziu cada parte da entrega.

☐ Mostra pelo menos 2 prompts que você escreveu ou adaptou, em blocos de código.

☐ Descreve pelo menos 2 erros concretos da IA que você pegou, com a evidência que denunciou cada erro e a correção feita.

☐ Explica onde está cada parte da entrega e a ordem sugerida de leitura.

### Restrições

☐ O último commit do repositório base é ancestral da `main` do fork: `git merge-base --is-ancestor <commit-do-base> main` termina com código 0.

☐ Fora de `docs/adrs/`, `docs/HLD.md`, `docs/c4/` e `README.md`, nada mudou em relação ao base: o comando abaixo não lista nenhum arquivo.

```
git diff --stat <commit-do-base> main -- . ':!docs/adrs' ':!docs/HLD.md' ':!docs/c4' ':!README.md'
```

## Fluxo do avaliador

**1.** Clone o fork completo (sem `--depth`) e confira os dois critérios de restrições.

**2.** Liste `docs/adrs/ADR-*.md`: quantidade dentro da faixa, numeração contínua e em ordem de data.

**3.** Em cada ADR, confira metadados, seções e data; extraia os hashes e caminhos citados e verifique cada um com os comandos do critério de evidências.

**4.** Cruze o conjunto com a lista interna: cobertura mínima, casos que não pedem ADR, relações entre decisões, Needs Input onde as fontes não respondem e divergência registrada onde as fontes discordam.

**5.** Abra `docs/adrs/README.md` no GitHub: lista completa, diagrama renderizado e coerente com os metadados, candidatos descartados justificados.

**6.** Leia o `docs/HLD.md` contra a lista interna do estado atual e confira os links para as ADRs.

**7.** Baixe o `plantuml.jar` das releases oficiais do PlantUML (exige Java), rode o `-checkonly` e leia os três níveis contra a mesma lista.

**8.** Leia o `README.md`.

Uma ADR com hash inexistente ou caminho inventado reprova o critério de evidências, por melhor que seja o texto. Neste desafio, o que não tem prova não conta.

## Entrega

Envie o link do seu fork público no GitHub, com tudo na branch `main`. Estrutura sugerida:

```
.
├── README.md
└── docs/
    ├── adrs/
    │   ├── README.md
    │   └── ADR-001-....md
    ├── HLD.md
    └── c4/
        ├── horalis-c1.puml
        ├── horalis-c2.puml
        └── horalis-c3.puml
```

## Dicas finais

Três tropeços não fazem parte do desafio, mas costumam travar. O primeiro é o clone raso: com `--depth`, o histórico some, e uma ferramenta de IA sem acesso ao terminal não lê o `git log` sozinha, então leve a saída até ela. O segundo são os plugins do professor. Eles rodam no Claude Code, mas por dentro são prompts e funcionam em outras ferramentas. O gerador de ADRs entrega um bom rascunho, mas fora do formato pedido aqui: em português, ele traduz os status e a marcação Needs Input, usa outro formato de data e não cita commits. O gerador de C4 parte de um FDD, documento que este repositório não tem; decida o que entregar a ele no lugar. O terceiro é deixar a compilação dos diagramas para o fim: rode o mesmo `-checkonly` do avaliador assim que o primeiro nível estiver pronto.

Por último, a IA vai ser rápida e convincente. Toda vez que ela afirmar o porquê de uma decisão, pergunte de onde saiu. Se a resposta for um palpite, você acabou de achar um Needs Input.