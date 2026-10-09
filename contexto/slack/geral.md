> Export do canal #geral (Slack), gerado em 2026-09-29 por Camila Rocha. Recorte de 2022 em diante; o histórico anterior é quase todo de avisos de reunião, aniversários e links de documentos que não existem mais. Anexos, reações e respostas em thread foram achatados em linhas simples.

[2022-01-10 09:02] Helena Duarte: bom dia, pessoal! feliz 2022. reunião geral na quinta às 10h, link no convite
[2022-01-10 09:10] Marcos Teixeira: bom dia!
[2022-02-04 16:20] Helena Duarte: fechamos contrato com a Rede Fisio Mais, 9 unidades. entram em março
[2022-02-04 16:22] Beatriz Nogueira: uhuuul
[2022-02-04 16:23] Rafael Lima: boa Helena!
[2022-03-18 11:00] Thiago Fonseca: aviso: o coworking vai ter dedetização sexta que vem, todo mundo em home office no dia 25
[2022-03-18 11:02] Juliana Prado: amo dedetização
[2022-04-12 10:31] Helena Duarte: gente, várias clínicas ligando com erro no sistema. o time técnico já tá sabendo?
[2022-04-12 10:33] Rafael Lima: sim, é o deploy de hoje. migration ainda rodando. call aberta pra quem tá envolvido, link da call no convite
[2022-04-12 10:35] Helena Duarte: ok. o que eu falo pros clientes?
[2022-04-12 10:38] Rafael Lima: que é uma atualização em andamento e que vai voltando clínica por clínica ao longo do dia. te passo previsão às 12h
[2022-04-12 15:50] Rafael Lima: tudo normalizado. obrigado pela paciência, postmortem sai essa semana
[2022-04-12 15:52] Helena Duarte: ufa. obrigada
[2022-06-14 17:30] Helena Duarte: lembrando: quinta é Corpus Christi, sexta é ponte. bom descanso
[2022-06-14 17:31] Thiago Fonseca: :palm_tree:
[2022-07-20 09:15] Beatriz Nogueira: hoje é aniversário do Marcos! tem bolo na copa às 16h
[2022-07-20 09:16] Marcos Teixeira: não precisava kkk
[2022-07-20 16:40] Juliana Prado: o bolo acabou em 7 minutos, recorde
[2022-10-03 10:00] Helena Duarte: cliente novo: Instituto Ortopédico Serra Azul, 4 unidades e 38 profissionais. maior conta até agora
[2022-10-03 10:02] Rafael Lima: :rocket:
[2022-10-03 10:03] Thiago Fonseca: boa!

[2022-11-08 21:12] Helena Duarte: gente, desculpa o horário. a recepção da Clínica Movimento me ligou: os lembretes estão saindo 3 horas adiantados
[2022-11-08 21:13] Helena Duarte: paciente com consulta amanhã às 9h recebeu o lembrete hoje às 6h da manhã. ela disse que o lembrete costuma chegar no fim da manhã do dia anterior
[2022-11-08 21:15] Helena Duarte: e outras duas clínicas mandaram msg parecida à tarde, achei que era coincidência
[2022-11-08 21:28] Juliana Prado: vi agora, vou olhar
[2022-11-08 21:30] Marcos Teixeira: tô online tbm se precisar
[2022-11-08 21:52] Juliana Prado: achei. no `lembretes:enfileirar` a comparação de horário tá sendo feita em UTC
[2022-11-08 21:53] Juliana Prado: a consulta usa o `now()` do Postgres, que roda em UTC, e o horário do agendamento tá gravado no horário de Brasília. aí ele acha que já é 3h mais tarde e enfileira adiantado
[2022-11-08 21:55] Marcos Teixeira: isso veio na reescrita do comando na unificação, em junho? como ninguém reclamou antes?
[2022-11-08 21:56] Juliana Prado: veio. lembrete de 24h chegando 27h antes ninguém percebe. quem reclamou foi quem recebeu de madrugada
[2022-11-08 22:05] Rafael Lima: tô aqui. precisa de ajuda?
[2022-11-08 22:06] Juliana Prado: não, já tô com a correção, só escrevendo o teste com o fuso de São Paulo
[2022-11-08 22:30] Juliana Prado: mais uns minutos, quero rodar a suíte inteira
[2022-11-08 23:41] Juliana Prado: hotfix no ar. o "agora" é calculado em PHP no fuso de cada clínica antes de comparar, e o teste roda com relógio fixo
[2022-11-08 23:42] Juliana Prado: lembretes que já estavam na fila pra amanhã eu reenfileirei com o horário certo
[2022-11-08 23:43] Rafael Lima: valeu Ju, vai dormir
[2022-11-08 23:45] Helena Duarte: obrigada!!! amanhã cedo eu aviso as clínicas
[2022-11-09 09:10] Helena Duarte: avisei as três clínicas, todas tranquilas
[2022-11-09 09:25] Juliana Prado: conferi o log da noite, nenhum lembrete adiantado depois do hotfix
[2022-12-12 14:00] Helena Duarte: confraternização dia 16, sexta, no bar de sempre. confirmem até quarta
[2022-12-12 14:05] Beatriz Nogueira: confirmada
[2022-12-12 14:06] Thiago Fonseca: confirmado
[2022-12-12 14:20] Marcos Teixeira: vou
[2022-12-12 14:31] Juliana Prado: eu também

[2023-01-10 10:00] Marcos Teixeira: pessoal, troquei o php-cs-fixer pelo Pint (o formatador oficial do laravel). config fica no pint.json na raiz
[2023-01-10 10:01] Marcos Teixeira: vai ter um commit grande só de formatação, uns 120 arquivos. subo umas 11h
[2023-01-10 10:02] Marcos Teixeira: depois disso todo mundo dá rebase na main antes de continuar o que está fazendo, senão o conflito vai ser feio
[2023-01-10 10:05] Beatriz Nogueira: dá pra ignorar esse commit no blame?
[2023-01-10 10:07] Marcos Teixeira: dá com --ignore-rev no blame. mando o hash aqui depois
[2023-01-10 10:10] Juliana Prado: tenho um PR aberto, mergeio antes?
[2023-01-10 10:12] Marcos Teixeira: mergeia até 10h50 que eu espero
[2023-01-10 11:20] Marcos Teixeira: commit de formatação na main. rebase, pessoal
[2023-01-11 09:30] Thiago Fonseca: rebase feito, sem dor
[2023-01-11 09:41] Beatriz Nogueira: o meu deu conflito em 3 arquivos mas foi tranquilo
[2023-02-16 17:00] Helena Duarte: segunda e terça de carnaval não tem expediente. quarta volta ao meio-dia
[2023-02-16 17:02] Marcos Teixeira: :confetti_ball:
[2023-04-19 09:20] Beatriz Nogueira: a máquina de café do coworking quebrou de novo
[2023-04-19 09:21] Thiago Fonseca: dia triste
[2023-04-19 11:45] Beatriz Nogueira: voltou!

[2023-05-09 11:00] Marcos Teixeira: as clínicas grandes reclamam da busca de pacientes: se a recepção digita o nome com erro ou sem acento não acha
[2023-05-09 11:01] Marcos Teixeira: o Thiago e eu queremos testar a busca com Laravel Scout + Meilisearch
[2023-05-09 11:02] Thiago Fonseca: eu subo o meilisearch num container do lado do worker, é leve
[2023-05-09 11:05] Helena Duarte: o que é isso em português? kkk
[2023-05-09 11:07] Marcos Teixeira: um buscador que entende quando a pessoa digita "Joao Sauza" querendo dizer "João Souza"
[2023-05-09 11:08] Helena Duarte: ah, isso eu quero
[2023-05-09 11:10] Rafael Lima: ok, mas é teste. duas semanas e a gente decide
[2023-05-09 11:12] Marcos Teixeira: fechado. vamos testar duas semanas e trazemos os números
[2023-05-18 16:00] Marcos Teixeira: indexação inicial feita, a busca por nome do painel tá usando o Scout
[2023-05-24 10:00] Juliana Prado: alguém viu que o índice da Serra Azul ficou desatualizado ontem? paciente novo não aparecia na busca
[2023-05-24 10:05] Thiago Fonseca: o meilisearch reiniciou de madrugada e as atualizações daquela hora se perderam. reindexei e vou colocar alerta
[2023-06-06 10:30] Marcos Teixeira: resultado do teste da busca: não compensou
[2023-06-06 10:31] Marcos Teixeira: é mais um serviço pra manter (índice, reindexação quando falha, backup, alerta), e pro volume atual a diferença foi pequena
[2023-06-06 10:32] Thiago Fonseca: a maior clínica tem uns 30 mil pacientes. a busca de hoje no banco dá conta, e se precisar dá pra melhorar o filtro por nome
[2023-06-06 10:33] Rafael Lima: tranquilo, era pra isso que servia o teste. pode reverter
[2023-06-06 10:35] Marcos Teixeira: revertido: três reverts na main, sai o Scout, o meilisearch do compose e a busca nova
[2023-06-07 14:10] Thiago Fonseca: container do meilisearch desligado
[2023-07-25 16:00] Helena Duarte: reunião geral sexta 10h, vou mostrar os números do semestre
[2023-07-25 16:03] Beatriz Nogueira: :+1:

[2023-09-12 10:00] Rafael Lima: anúncio: a partir de hoje o WhatsApp é o canal principal dos lembretes em todas as clínicas. SMS fica de fallback
[2023-09-12 10:01] Rafael Lima: o whatsapp só vai pra paciente com `aceita_whatsapp` marcado. sem aceite, ou se a mensagem falhar, sai SMS como antes
[2023-09-12 10:02] Rafael Lima: números do piloto (40 clínicas, julho e agosto): faltas caíram de 18% pra 11%
[2023-09-12 10:03] Helena Duarte: GENTE!!! 18 pra 11!!!
[2023-09-12 10:03] Helena Duarte: eu peço isso desde 2020 kkkkk obrigada time, de verdade
[2023-09-12 10:05] Marcos Teixeira: entrou como mais uma implementação do `CanalLembrete`, com uma cadeia que tenta o whatsapp e cai pro sms. quase não mexemos no job
[2023-09-12 10:06] Juliana Prado: a interface de 2020 se pagou
[2023-09-12 10:09] Beatriz Nogueira: o aceite fica no cadastro do paciente no painel. no app entra na próxima versão
[2023-09-12 10:15] Thiago Fonseca: pergunta de quem chegou depois: por que não ficamos só no SMS, já que funcionava?
[2023-09-12 10:18] Rafael Lima: o SMS é o 18% do piloto. é caro por mensagem e paciente lê muito menos que whatsapp
[2023-09-12 10:20] Thiago Fonseca: e por que direto na Cloud API da Meta e não num BSP? achei que era o caminho mais comum
[2023-09-12 10:24] Juliana Prado: olhamos dois BSPs. cobram por mensagem em cima do que a meta já cobra, e seria mais um intermediário guardando dado de paciente
[2023-09-12 10:25] Rafael Lima: e a Cloud API direta ficou simples: template aprovado no gerenciador da Meta e uma chamada HTTP por lembrete
[2023-09-12 10:27] Thiago Fonseca: entendi, faz sentido
[2023-09-12 10:30] Helena Duarte: posso divulgar os números pros clientes?
[2023-09-12 10:32] Rafael Lima: pode, só fala "nas clínicas do piloto"
[2023-09-13 09:40] Helena Duarte: post no linkedin no ar, já teve 3 clínicas pedindo demo
[2023-09-13 09:42] Beatriz Nogueira: :fire:
[2023-10-17 15:00] Helena Duarte: montando a apresentação pro conselho. o custo de infra caiu bem desde o ano passado, né? a unificação do banco foi pra economizar no RDS
[2023-10-17 15:04] Thiago Fonseca: sim, a conta caiu bastante depois disso
[2023-10-17 15:05] Helena Duarte: perfeito, vou colocar no slide
[2023-11-14 09:00] Thiago Fonseca: hoje é aniversário da Beatriz! parabéns!
[2023-11-14 09:02] Helena Duarte: parabéns Bia!!
[2023-11-14 09:05] Beatriz Nogueira: obrigadaaa
[2023-12-19 17:30] Juliana Prado: pessoal, hoje é meu último dia. foram quase cinco anos, desde a primeira linha de código. obrigada por tudo
[2023-12-19 17:32] Helena Duarte: Ju, o Horalis não existiria sem você. sucesso!
[2023-12-19 17:33] Rafael Lima: obrigado por tudo, Juliana
[2023-12-19 17:35] Marcos Teixeira: vai fazer muita falta
[2023-12-19 17:36] Beatriz Nogueira: :heart:

[2024-01-08 09:05] Helena Duarte: bom dia e feliz 2024! reunião geral quinta às 10h
[2024-04-02 11:00] Helena Duarte: cliente novo: Clínica Bem Viver, 6 unidades em Campinas
[2024-04-02 11:03] Thiago Fonseca: boa!
[2024-06-28 16:50] Helena Duarte: hoje é o último dia do Rafael. foram cinco anos construindo isso juntos. obrigada, sócio
[2024-06-28 16:55] Rafael Lima: obrigado vocês. tô a um zap de distância
[2024-08-05 09:30] Helena Duarte: bem-vinda, Camila Rocha, nossa nova tech lead!
[2024-08-05 09:32] Camila Rocha: obrigada! ansiosa pra conhecer todo mundo
[2024-08-05 09:33] Marcos Teixeira: bem-vinda!
[2024-11-20 10:00] Helena Duarte: lembrando que hoje é feriado em SP, quem estiver trabalhando de outro estado fica à vontade
[2024-12-13 15:00] Camila Rocha: congelamento de deploy de 20/12 a 6/1, só hotfix
[2025-02-03 10:00] Helena Duarte: chegamos a 900 clínicas ativas. obrigada time!
[2025-02-03 10:02] Camila Rocha: :tada:
[2025-07-22 09:15] Beatriz Nogueira: café da manhã de boas-vindas pros estagiários sexta, na copa
