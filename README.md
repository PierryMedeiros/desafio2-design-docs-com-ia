# Horalis: arqueologia de decisões

Este repositório é a minha entrega do desafio "Arqueologia de Decisões": reconstruir, a partir do código, do histórico do git e dos rastros incompletos, as decisões arquiteturais da Horalis e o retrato da arquitetura de hoje, sem inventar nada. O enunciado original está no commit [`70594f5`](https://github.com/PierryMedeiros/desafio2-design-docs-com-ia/blob/70594f53c4e980914893df8a13e78377d43f7527/README.md), que é o último commit do repositório base.

O código, a configuração, os testes e os rastros (`contexto/`, `docs/ARQUITETURA.md`, `docs/postmortems/`) estão como vieram. Todo o meu trabalho está em `docs/adrs/`, `docs/HLD.md`, `docs/c4/` e neste README.

## Como navegar

Ordem sugerida de leitura:

1. **[`docs/HLD.md`](docs/HLD.md):** a Horalis como está no HEAD (componentes, fluxos, dados, interfaces, segurança, observabilidade e riscos). A seção 11 lista o que deixou de existir, para não confundir com o `docs/ARQUITETURA.md` de 2019.
2. **[`docs/c4/`](docs/c4/):** os diagramas de contexto ([`horalis-c1.puml`](docs/c4/horalis-c1.puml)), de containers ([`horalis-c2.puml`](docs/c4/horalis-c2.puml)) e de componentes da aplicação Laravel ([`horalis-c3.puml`](docs/c4/horalis-c3.puml)), todos do estado atual.
3. **[`docs/adrs/README.md`](docs/adrs/README.md):** o índice das 14 ADRs, com a linha do tempo de 2019 a 2023, o grafo Mermaid das relações e os candidatos que avaliei e descartei pela regra dos 3 Es.
4. **As ADRs**, em ordem. As que mais explicam a história são a [ADR-003](docs/adrs/ADR-003-um-schema-por-clinica-no-banco.md) e a [ADR-013](docs/adrs/ADR-013-schema-unico-com-tenant-id-e-escopo-global.md) (multi-tenancy, com a divergência de motivo), a [ADR-012](docs/adrs/ADR-012-criptografia-de-campo-para-cpf-e-notas-clinicas.md) (condição da DPO) e a [ADR-009](docs/adrs/ADR-009-migracao-da-infraestrutura-para-a-aws.md) (o caso mais claro de Needs Input).
5. **[`docs/adrs/rascunhos-ia/`](docs/adrs/rascunhos-ia/LEIA-ME.md)** (opcional): as saídas brutas dos plugins (mapeamento, 45 ADRs potenciais e dois testes do gerador). Não são ADRs e ficam como registro do processo.

Para compilar os diagramas:

```
java -jar plantuml.jar -checkonly "docs/c4/*.puml"
```

## Ferramentas de IA e o papel de cada uma

| Ferramenta | Para que usei |
|---|---|
| **Claude Code** (terminal) | Agente principal. Leu o código e o `git log`/`git show` direto no terminal, cruzou as fontes, escreveu os rascunhos de ADR, HLD e README que eu revisei e rodou as verificações (hashes, caminhos, numeração, compilação do PlantUML, render do Mermaid). |
| **Plugin `adrs-management`** (marketplace do professor) | `/adr-map` para o mapeamento modular, `/adr-identify` para listar ADRs potenciais por módulo, `/adr-generate` testado em dois potenciais e `/adr-link --report-only` para auditar as relações que eu declarei. |
| **Plugin `diagrams-generator`** (marketplace do professor) | `/c4-generate` para o primeiro rascunho dos três níveis do C4. Como não há FDD no repositório, entreguei o `docs/HLD.md` no lugar dele. |
| **PlantUML 1.2025.4** e **mermaid-cli 11.4.2** | Não são IA. Usei para conferir que os diagramas compilam e renderizam. |

## Como organizei o trabalho

1. **Enunciado e rastros primeiro.** Li o `contexto/LEIA-ME.md`, como o enunciado pede, depois as duas atas, o e-mail da DPO, os dois canais do Slack, o `docs/ARQUITETURA.md` e o postmortem. Anotei cada trecho com cara de decisão (opções, "fechado", "descartada") e cada trecho em que as fontes discordam.
2. **Histórico do git.** Li o `git log --reverse` completo (164 commits, de 2019-04-01 a 2025-11-18) e abri com `git show --stat` os commits que batiam com as decisões anotadas. Montei uma linha do tempo cruzando a data de cada discussão com a do commit que a implementa.
3. **Plugins para não deixar passar nada.** Rodei o `/adr-map` com `--context-dir=contexto` e o `/adr-identify` em 8 módulos (pulei PAINEL e AGENDA, que o mapeamento classificou como de baixo risco). Comparei os 45 potenciais com a minha lista. O plugin me fez rever três pontos: o `TrustProxies` revertido, o `expires_at` sem uso no Sanctum e o `tenant_id` fora do payload dos jobs.
4. **Escolha das ADRs.** Fiquei com 14 decisões, cada uma com um commit e um rastro que sustentam a data. O resto foi para a lista de descartados (3 Es) ou para "consolidados em outra ADR" no índice.
5. **HLD antes do C4.** Escrevi o HLD conferindo cada afirmação no código do HEAD, com os links para as ADRs vigentes. Depois usei o HLD como entrada do gerador de C4 e corrigi o resultado à mão.
6. **Índice e verificação.** Escrevi o índice e rodei um script que confere o padrão de nome, a numeração, a ordem das datas, os status, as seções, a existência de cada hash (`git cat-file -e`) e de cada caminho (`git log --all -- <caminho>`) e os links. Também rodei o `-checkonly` do PlantUML e renderizei o Mermaid com o mermaid-cli.

## Prompts que escrevi ou adaptei

**1. Fase 2 do plugin, adaptada para legado.** O prompt padrão do `/adr-identify` olha o código atual e usa `git log --since="2 years ago"` nos exemplos. Isso perderia o schema por clínica, que já não existe no código. Acrescentei o histórico completo e as decisões revertidas:

```
Identify potential ADRs for the TENANCY module with --output-dir=docs/adrs

Restrictions: read only inside the repository, only the current branch,
do not modify any file outside docs/adrs/potential-adrs/ and the index, no commits.
Use the full git history (2019-2025, not just the last 2 years) and the traces in
contexto/, docs/ARQUITETURA.md and docs/postmortems/. Include decisions that were
later superseded or reverted (they may no longer be in the code).
Write in Portuguese (pt-BR). Cite real commit hashes only.
```

**2. Gerador de C4 sem FDD.** O `/c4-generate` parte de um FDD, que este repositório não tem. Entreguei o HLD e restringi o resultado ao estado atual:

```
Generate C4 diagrams from the Feature Design Document located at docs/HLD.md.
(Note: this project has no FDD; the HLD of the current state is used in its place.)
Output folder: <rascunho fora do repositório>
Feature name for files: horalis
PNG generation: DISABLED
- Write labels in Portuguese (pt-BR). Show only what exists TODAY (section 11 of
  the HLD lists what no longer exists: do not draw it). In the container diagram,
  every relationship must state technology/protocol.
```

**3. Auditoria das relações sem deixar o agente editar.** Usei o `/adr-link` só como revisor:

```
Run /adr-link in --report-only mode with --adrs-path=docs/adrs (the 14 ADRs are
the files ADR-*.md directly in that folder, flat, no module subfolders).
REPORT ONLY: do NOT modify any ADR file. (1) list the relations already declared,
(2) check bidirectionality of supersedes/amends pairs and that superseded ADRs have
status Superseded, (3) check all links resolve, (4) suggest relations you think are
missing, each with the evidence (source file/commit) that supports it.
```

**4. A pergunta que repeti em toda ADR.** Para cada motivo que o rascunho trazia, perguntei:

```
De onde saiu esse motivo? Cite o arquivo e a linha (ata, e-mail, Slack, postmortem
ou mensagem de commit). Se não houver fonte, troque a frase por "Needs Input:" e
diga exatamente o que falta saber.
```

## Erros da IA que eu peguei

**1. Motivos inventados para a migração para a AWS.** O `/adr-generate` aplicado ao potencial de infraestrutura marcou corretamente o motivo como "[PRECISA DE INFORMAÇÃO]". Na mesma ADR, porém, listou como "Fatores de Decisão" coisas como "Eliminar o ponto único de falha" e "Permitir mais de uma instância", e concluiu que a AWS foi escolhida "porque remove o servidor único, entrega redundância...". Esses motivos não aparecem em nenhuma fonte.

- **Evidência:** o único registro é o anúncio já concluído no Slack: "a migração da VPS pra AWS terminou na sexta" (`contexto/slack/arquitetura.md`, 2021-06-07). E o `contexto/LEIA-ME.md` avisa que há decisões "sem a discussão que levou até elas". A segunda instância só veio dois meses depois, em 2021-08-09.
- **Correção:** na [ADR-009](docs/adrs/ADR-009-migracao-da-infraestrutura-para-a-aws.md), contexto, opções e decisão trazem Needs Input. Os fatos da época (disco a 85%, Postgres apertado, SQS adiado) estão listados com a ressalva de que nenhuma fonte os liga à migração.

**2. Contêiner de scheduler atribuído ao postmortem.** O mesmo rascunho dizia que "a topologia atual (descrita no postmortem de 2022) tem [...] um contêiner de worker, um contêiner de scheduler".

- **Evidência:** a seção "Contexto da infraestrutura" de `docs/postmortems/2022-04-12-deploy-travado.md` lista um contêiner de worker e não fala de scheduler. O scheduler em contêiner só aparece no `docker-compose.yml` (`d93c79b`).
- **Correção:** a ADR-009 e o HLD citam o scheduler pelo commit e pelo compose, e o postmortem só pelo que ele de fato diz.

**3. `TrustProxies` dado como configurado no HLD.** No primeiro rascunho do HLD, o Claude Code escreveu que "`TrustProxies` está configurado para o balanceador (`69b2887`)", olhando só a mensagem do commit de 2021.

- **Evidência:** o agente de infraestrutura do `/adr-identify` apontou que o upgrade para Laravel 10 reverteu a configuração. Conferi: `git show 40d1dc9 -- app/Http/Middleware/TrustProxies.php` troca `$proxies = '*'` por `$proxies;`, e o arquivo no HEAD está sem valor.
- **Correção:** o HLD descreve o estado real e registra o problema como risco 12.

**4. ADRs do gerador fora do formato do desafio.** As duas ADRs do `/adr-generate` vieram com status traduzido ("Aceita", "Aceito"), data em DD-MM-AAAA ("20-05-2022"), relações como "ADRs Relacionados" sem os termos supersedes/depends on, número `XXX` e nenhum hash nem caminho de arquivo. A de tenancy declarou que "não consultou o git".

- **Evidência:** o enunciado exige data AAAA-MM-DD, os status em inglês do curso e pelo menos um hash e um caminho por ADR. O próprio prompt do gerador proíbe seções fora das 7 do MADR, o que deixa de fora a seção de evidências.
- **Correção:** escrevi as 14 ADRs no formato do enunciado. Os textos do gerador ficaram só como rascunho em `docs/adrs/rascunhos-ia/generated/`.

**5. Índice dos potenciais sobrescrito.** Os 8 agentes do `/adr-identify` rodaram em paralelo e gravaram o mesmo `potential-adrs-index.md`, cada um apagando a versão do anterior. Vários avisaram isso no relatório final.

- **Correção:** não usei esse índice como fonte. Trabalhei a partir dos arquivos individuais e da minha própria lista.

## Decisões de julgamento que vale conhecer

- **Busca com Meilisearch fora das ADRs.** Foi um teste de duas semanas, revertido. Pela regra dos 3 Es ele falha em "Estável", então ficou na lista de descartados do índice, com o motivo do time para não repetir.
- **Divergência sobre a unificação do banco.** Na [ADR-013](docs/adrs/ADR-013-schema-unico-com-tenant-id-e-escopo-global.md), adotei a ata de 2022-05-20 (motivo: deploy) contra o Slack de 2023-10-17 (motivo: economia de RDS) e expliquei por quê.
- **Plano de 2019 nunca realizado.** O GraphQL e o microsserviço de agenda do `docs/ARQUITETURA.md` aparecem como divergência nas ADRs 001 e 007, com Needs Input sobre o abandono.
