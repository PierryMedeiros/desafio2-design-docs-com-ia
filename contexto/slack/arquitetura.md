> Export do canal #arquitetura (Slack), gerado em 2026-09-29 por Camila Rocha. Exportação do histórico completo do canal em ordem cronológica. Anexos, reações e respostas em thread foram achatados em linhas simples. Algumas mensagens apagadas pelos autores não aparecem.

[2019-05-14 10:02] Rafael Lima: criei esse canal pra gente discutir coisa de arquitetura sem perder no meio do #geral
[2019-05-14 10:05] Juliana Prado: :+1:
[2019-05-14 10:07] Juliana Prado: já aproveitando: o middleware de clínica tá setando o search_path certinho, testei com dois schemas aqui
[2019-05-14 10:09] Rafael Lima: show. o `tenants:migrate` também tá rodando, roda tudo de database/migrations/tenant em cada schema
[2019-06-03 15:40] Juliana Prado: lembrete por e-mail pronto. comando `lembretes:enviar` no cron a cada 10 min, pega quem tem atendimento nas próximas 24h e manda
[2019-06-03 15:42] Rafael Lima: boa. por enquanto síncrono mesmo, são 3 clínicas
[2019-07-01 09:00] Rafael Lima: MVP no ar pras três parceiras. obrigado pessoal, foi puxado
[2019-07-01 09:03] Juliana Prado: \o/

[2020-02-11 09:14] Marcos Teixeira: bom dia. a Clínica Movimento ligou de novo reclamando que paciente recebeu o lembrete duas vezes, às vezes três
[2020-02-11 09:15] Marcos Teixeira: olhei o log e o `lembretes:enviar` ontem às 18h levou 14 min pra rodar
[2020-02-11 09:16] Marcos Teixeira: como o cron chama a cada 10 min, a próxima execução começa antes da anterior acabar e as duas pegam os mesmos agendamentos
[2020-02-11 09:21] Juliana Prado: putz. quando eu escrevi isso eram 3 clínicas e rodava em 20 segundos kkk
[2020-02-11 09:22] Juliana Prado: o envio é síncrono, uma conexão smtp por e-mail, e o smtp da VPS fica lento de tarde
[2020-02-11 09:30] Rafael Lima: dá pra colocar withoutOverlapping no schedule como paliativo?
[2020-02-11 09:31] Juliana Prado: dá, mas aí a execução seguinte só pula e os lembretes atrasam. resolve a duplicação, não o tempo
[2020-02-11 09:33] Rafael Lima: então vamos tirar o envio de dentro do comando, e o withoutOverlapping entra junto
[2020-02-11 09:35] Marcos Teixeira: fila? o laravel já tem tudo pronto pra isso
[2020-02-11 09:36] Rafael Lima: provavelmente. amanhã a gente fecha
[2020-02-11 11:02] Juliana Prado: por enquanto avisei a Clínica Movimento que a gente tá resolvendo
[2020-02-12 10:10] Marcos Teixeira: pensando na fila. opções que eu vi: driver database, redis, sqs
[2020-02-12 10:12] Marcos Teixeira: database é o mais fácil, não precisa subir nada novo, só a tabela jobs
[2020-02-12 10:15] Rafael Lima: database eu não queria. o worker fica fazendo polling e lock na tabela jobs o tempo todo, isso pesaria no Postgres, que já fica apertado na VPS de tarde
[2020-02-12 10:16] Rafael Lima: e ainda tem a dúvida de em qual schema essa tabela ficaria
[2020-02-12 10:18] Juliana Prado: sqs seria legal mas a gente nem tá na aws
[2020-02-12 10:19] Rafael Lima: pois é. não vou abrir conta na aws só pra ter fila. se um dia a gente for pra lá a gente revê
[2020-02-12 10:21] Marcos Teixeira: então redis. sobe um container de redis na VPS e roda o worker num container separado
[2020-02-12 10:23] Juliana Prado: redis eu topo, é leve
[2020-02-12 10:25] Rafael Lima: fechado: redis + worker dedicado. o comando vira `lembretes:enfileirar`, só seleciona o que tem que sair e despacha um job por lembrete
[2020-02-12 10:26] Rafael Lima: job `EnviarLembreteAgendamento`, na fila `notificacoes`. separa da default pq vai ter outras coisas lá
[2020-02-12 10:27] Marcos Teixeira: fechou, eu pego
[2020-02-12 10:28] Juliana Prado: e precisa de uma trava pra não despachar o mesmo lembrete duas vezes se o worker atrasar
[2020-02-12 10:29] Marcos Teixeira: boa, dá pra fazer com um lock no cache por agendamento
[2020-02-13 16:40] Marcos Teixeira: PR da fila aberto, quem puder revisar
[2020-02-13 17:05] Juliana Prado: revisando
[2020-02-13 17:48] Juliana Prado: aprovei com dois comentários bobos
[2020-02-14 15:30] Marcos Teixeira: posso fazer deploy da fila hoje?
[2020-02-14 15:31] Rafael Lima: sexta 15h30? nem pensar kkk segunda de manhã
[2020-02-14 15:31] Marcos Teixeira: justo
[2020-02-17 09:40] Marcos Teixeira: fila no ar. worker rodando no container `worker`, com supervisor reiniciando se cair
[2020-02-17 11:20] Juliana Prado: o enfileirar tá rodando em 3 segundos agora
[2020-02-18 09:05] Marcos Teixeira: nenhuma reclamação de duplicado desde ontem

[2020-03-10 10:02] Helena Duarte: gente, conversei com 6 clínicas essa semana e todas falam a mesma coisa: paciente não lê e-mail
[2020-03-10 10:03] Helena Duarte: a Odonto Vila Mariana mediu, faltas subiram pra 22% em fevereiro
[2020-03-10 10:05] Rafael Lima: o que elas pedem?
[2020-03-10 10:06] Helena Duarte: SMS. e muitas pedem WhatsApp, mas sei que isso é mais complicado
[2020-03-10 10:10] Juliana Prado: whatsapp oficial hoje é bem burocrático. sms dá pra fazer rápido
[2020-03-10 10:12] Marcos Teixeira: não dá pra continuar só no e-mail e melhorar o texto/horário? tipo mandar 24h antes e de novo 2h antes
[2020-03-10 10:14] Helena Duarte: acho que não resolve. o paciente não abre o e-mail, não é questão de horário
[2020-03-10 10:15] Rafael Lima: concordo com a Helena. ficar só no e-mail não resolve as faltas. vamos de SMS
[2020-03-10 10:18] Rafael Lima: provedor externo, não vou inventar nada. olhei o Twilio, API simples e tem número brasileiro
[2020-03-10 10:22] Helena Duarte: ótimo. e um dia vai ter WhatsApp, deixa preparado
[2020-03-10 10:24] Rafael Lima: ok. então o job não chama o Twilio direto. cria uma interface `CanalLembrete` com `enviar($paciente, $mensagem)`, e o e-mail vira só uma implementação dela
[2020-03-10 10:24] Rafael Lima: o SMS é outra implementação. quando vier whatsapp é só mais uma
[2020-03-10 10:25] Juliana Prado: e quem escolhe o canal? a clínica nas configurações?
[2020-03-10 10:27] Rafael Lima: por enquanto simples: um canal pra todo mundo, configurado no .env
[2020-03-10 10:30] Rafael Lima: eu faço a interface hoje. Marcos, você pluga o Twilio em cima?
[2020-03-10 10:31] Marcos Teixeira: fechado
[2020-03-11 14:10] Marcos Teixeira: conta do Twilio criada. credenciais no .env de produção, passei pro Rafael pelo cofre de senhas
[2020-03-11 14:12] Helena Duarte: quanto custa cada sms mesmo?
[2020-03-11 14:15] Marcos Teixeira: uns centavos, te mando a planilha
[2020-03-16 11:45] Marcos Teixeira: SMS em produção nas 3 parceiras primeiro. o resto na semana que vem se não aparecer problema
[2020-03-16 11:47] Helena Duarte: maravilha!!

[2020-05-06 14:20] Marcos Teixeira: o disco da VPS tá em 85%, quem cuida dos backups?
[2020-05-06 14:24] Rafael Lima: eu. tem dump velho lá, limpo hoje
[2020-05-06 14:25] Marcos Teixeira: valeu
[2020-05-11 09:10] Rafael Lima: limpei, 52% agora
[2020-10-05 16:00] Juliana Prado: alguém revisa meu PR dos status faltou e realizado? é pequeno
[2020-10-05 16:30] Marcos Teixeira: vou olhar
[2020-10-05 16:58] Marcos Teixeira: aprovado
[2020-11-27 16:50] Marcos Teixeira: black friday e eu aqui querendo fazer deploy na sexta
[2020-11-27 16:51] Juliana Prado: não
[2020-11-27 16:51] Rafael Lima: não
[2020-11-27 16:52] Marcos Teixeira: ok ok

[2021-02-17 10:00] Rafael Lima: Beatriz e eu fechamos o escopo do app do paciente. primeira versão: ver horários livres, marcar, ver os agendamentos e cancelar
[2021-02-17 10:02] Beatriz Nogueira: vou precisar de uma API. hoje tudo é Blade, né?
[2021-02-17 10:03] Rafael Lima: tudo blade. a api mora no mesmo monolito, em routes/api.php
[2021-02-17 10:10] Beatriz Nogueira: proposta: REST com a versão na URL, `/api/v1/...`. app de loja não dá pra forçar atualização, tem paciente que vai ficar meses com a versão velha instalada
[2021-02-17 10:11] Beatriz Nogueira: então a gente não pode quebrar contrato. se mudar muito, vira /api/v2 e a v1 continua viva até o pessoal atualizar
[2021-02-17 10:13] Marcos Teixeira: versão no header não seria mais "correto"?
[2021-02-17 10:15] Beatriz Nogueira: até seria, mas na URL é mais fácil de enxergar no log e de rotear. e no app é só a base url
[2021-02-17 10:16] Rafael Lima: vai de URL. simples
[2021-02-17 10:20] Beatriz Nogueira: autenticação: pensei em JWT com tymon/jwt-auth, que eu usava no meu emprego anterior
[2021-02-17 10:24] Juliana Prado: jwt stateless é chato de revogar. paciente perde o celular, clínica bloqueia o paciente, como derruba o token?
[2021-02-17 10:25] Beatriz Nogueira: blacklist no cache, ou token curto com refresh
[2021-02-17 10:27] Rafael Lima: aí a gente reinventa sessão em cima de jwt. o Sanctum resolve: token opaco guardado no banco, revogar é apagar a linha
[2021-02-17 10:28] Beatriz Nogueira: verdade, e é oficial do laravel, menos uma dependência de terceiro. topo Sanctum
[2021-02-17 10:30] Rafael Lima: fechado: REST, /api/v1, Sanctum com um token por dispositivo
[2021-02-18 15:12] Beatriz Nogueira: a tabela personal_access_tokens fica em qual schema? o paciente loga antes de a gente saber a clínica
[2021-02-18 15:20] Rafael Lima: no schema da clínica. o app manda o slug da clínica no login e no header X-Clinica, e o middleware ajusta o search_path antes do sanctum
[2021-02-22 11:00] Beatriz Nogueira: rascunho do contrato da v1 tá no Postman, compartilhei o workspace com vocês
[2021-02-22 11:30] Marcos Teixeira: não apareceu nada pra mim
[2021-02-22 11:32] Beatriz Nogueira: ops, tava no workspace privado. tenta agora
[2021-02-22 11:33] Marcos Teixeira: foi
[2021-03-24 10:05] Beatriz Nogueira: /api/v1 em produção. app enviado pras lojas, esperando revisão da apple
[2021-03-24 10:06] Rafael Lima: :tada:

[2021-06-07 09:30] Rafael Lima: pessoal, o Thiago começou hoje e vai cuidar de infra. Thiago, esse é o canal onde a gente briga sobre arquitetura kkk
[2021-06-07 09:32] Thiago Fonseca: opa, bom dia! prazer
[2021-06-07 09:35] Rafael Lima: e pra registrar aqui: a migração da VPS pra AWS terminou na sexta. RDS, ElastiCache, aplicação e worker rodando lá. a VPS desliga dia 30
[2021-06-07 09:36] Juliana Prado: finalmente
[2021-06-07 09:40] Thiago Fonseca: vou passar a semana lendo o que tem de infra, depois venho com um monte de pergunta
[2021-07-14 14:02] Thiago Fonseca: dúvida boba: nome de branch é feature/ ou feat/?
[2021-07-14 14:05] Marcos Teixeira: feat/ e fix/. mas ninguém segue muito kkk
[2021-07-14 14:06] Juliana Prado: eu sigo!
[2021-07-14 14:06] Marcos Teixeira: a Juliana segue

[2021-08-10 09:12] Thiago Fonseca: subi ontem no fim do dia a segunda instância da aplicação atrás do balanceador
[2021-08-10 10:40] Marcos Teixeira: a Helena repassou que a Clínica Movimento diz que "o anexo sumiu". o exame que a recepção subiu de manhã dá 404 quando abre
[2021-08-10 10:42] Marcos Teixeira: e eu consegui reproduzir uma vez sim uma vez não
[2021-08-10 10:45] Juliana Prado: anexo vai pra storage/app/anexos, disco local do servidor. upload caiu numa instância e o download na outra
[2021-08-10 10:46] Thiago Fonseca: faz sentido. e acho que explica outra reclamação de hoje: gente sendo deslogada do nada
[2021-08-10 10:47] Juliana Prado: sessão também tá em arquivo, storage/framework/sessions. trocou de instância, perdeu a sessão
[2021-08-10 10:50] Rafael Lima: tira a segunda instância por enquanto
[2021-08-10 10:51] Thiago Fonseca: tirei, voltamos pra uma até resolver
[2021-08-10 11:05] Rafael Lima: anexos vão pro S3. download por URL pré-assinada (`temporaryUrl`), expirando em poucos minutos. o arquivo nunca fica público
[2021-08-10 11:06] Rafael Lima: e no mesmo movimento a sessão vai pro Redis, que a gente já tem no ElastiCache
[2021-08-10 11:08] Thiago Fonseca: eu crio o bucket com bloqueio de acesso público e criptografia, e uma role pra aplicação só com put/get nele
[2021-08-10 11:10] Juliana Prado: eu faço o código do upload e do download
[2021-08-10 11:12] Thiago Fonseca: e eu o disco s3 (minio no compose de dev) e um comando pra copiar o que já tá em storage/app/anexos
[2021-08-10 11:13] Rafael Lima: sessão é só SESSION_DRIVER=redis, usa a conexão que já existe
[2021-08-12 10:15] Thiago Fonseca: bucket e role prontos em produção
[2021-08-12 11:40] Thiago Fonseca: comando `anexos:migrar-para-s3` no PR, ele copia arquivo por arquivo e atualiza o caminho no banco
[2021-08-13 15:20] Thiago Fonseca: deploy disso segunda cedo, né? já aprendi a regra da sexta
[2021-08-13 15:21] Marcos Teixeira: aprendeu rápido
[2021-08-18 08:50] Thiago Fonseca: anexos copiados (uns 41 mil arquivos), sessão no redis, duas instâncias de volta no balanceador
[2021-08-18 10:02] Marcos Teixeira: testei upload e download alternando instância, tudo ok
[2021-08-18 10:05] Juliana Prado: amanhã apago os arquivos do disco local depois de conferir as contagens
[2021-08-19 11:40] Juliana Prado: conferido e apagado

[2021-11-03 15:30] Beatriz Nogueira: alguém revisa o PR de push notification do app? toca só na api
[2021-11-03 15:55] Rafael Lima: olho amanhã cedo
[2022-01-19 14:00] Beatriz Nogueira: o link pra doc da api no README tá quebrado, aponta pro workspace antigo do postman
[2022-01-19 14:08] Marcos Teixeira: arrumei, valeu
[2022-04-12 10:18] Thiago Fonseca: Sentry cheio de "column convenio does not exist". o tenants:migrate tá rodando, uns 50s por schema
[2022-04-12 10:19] Juliana Prado: são 380 schemas... 380 x 50s dá mais de 5 horas
[2022-04-12 10:20] Rafael Lima: vamos pro call, link no #geral
[2022-04-14 17:02] Thiago Fonseca: postmortem de terça no drive, quem puder lê e comenta até amanhã
[2022-04-14 17:30] Beatriz Nogueira: li, comentei duas coisas na parte do app
[2022-04-20 11:30] Juliana Prado: ação do postmortem feita: o `tenants:migrate` agora registra no log quanto cada schema levou
[2022-05-20 12:30] Rafael Lima: ata da reunião de hoje tá no Notion, com os responsáveis. qualquer dúvida comenta lá
[2022-06-27 09:10] Thiago Fonseca: janela de sábado concluída, contagens batendo em todas as tabelas. Sentry tranquilo até agora
[2022-06-27 09:12] Rafael Lima: obrigado Thiago e todo mundo que ficou no sábado
[2022-09-14 11:20] Marcos Teixeira: alguém sabe se o staging tá de pé? tá dando 502
[2022-09-14 11:26] Thiago Fonseca: tava reiniciando, já voltou
[2022-12-15 16:45] Juliana Prado: sexta é o último deploy do ano, depois só hotfix até 9/1
[2022-12-15 16:46] Thiago Fonseca: anotado

[2023-07-05 10:00] Rafael Lima: laravel 10 + php 8.2 rodando em staging. testes passando, vou deixar uns dias antes de ir pra produção
[2023-07-05 10:04] Beatriz Nogueira: a api não mudou nada pra fora, né?
[2023-07-05 10:05] Rafael Lima: nada, só dependência. e o composer.lock vai passar a ser versionado
[2023-08-01 14:30] Beatriz Nogueira: só pra registrar: o app 3.0 continua usando /api/v1, não precisou de v2
[2023-08-01 14:31] Rafael Lima: ótimo
[2024-02-07 09:40] Thiago Fonseca: atualização do RDS agendada pra quinta 22h, uns 10 min de indisponibilidade
[2024-02-07 09:42] Marcos Teixeira: :+1:

[2024-03-12 09:50] Thiago Fonseca: ontem à tarde o provedor de SMS ficou instável (Twilio estourando timeout direto) e vi job de lembrete preso e alguns repetidos
[2024-03-12 09:52] Thiago Fonseca: o job tava com timeout de 120s e 5 tentativas, e o worker com o timeout default de 60s. o worker matava o job antes, ele voltava pra fila, e às vezes o SMS já tinha saído
[2024-03-12 09:55] Thiago Fonseca: vou ajustar no `EnviarLembreteAgendamento`: `$timeout = 30`, `$tries = 3` e `$backoff` de 30, 120 e 300 segundos
[2024-03-12 09:56] Thiago Fonseca: e no worker `--tries=3 --timeout=60`, um pouco acima do job
[2024-03-12 09:57] Marcos Teixeira: faz sentido. o retry_after da conexão redis tem que ser maior que o timeout do worker tbm
[2024-03-12 09:58] Thiago Fonseca: sim, já tá em 90
[2024-03-12 10:01] Rafael Lima: :+1: manda
[2024-03-13 11:20] Thiago Fonseca: no ar. nenhum job preso desde ontem

[2024-06-28 17:00] Rafael Lima: último dia por aqui. foi um prazer construir isso com vocês. o canal é de vocês agora
[2024-06-28 17:03] Beatriz Nogueira: vai fazer falta!
[2024-06-28 17:04] Thiago Fonseca: valeu por tudo, Rafael
[2024-08-05 10:15] Camila Rocha: oi pessoal! sou a Camila, comecei hoje como tech lead. vou ler o histórico daqui com calma
[2024-08-05 10:17] Marcos Teixeira: bem-vinda! boa sorte com o histórico kkk
[2024-08-05 10:18] Beatriz Nogueira: bem-vinda Camila!
[2025-03-11 15:00] Thiago Fonseca: compose de dev agora espera o postgres e roda migrate e seed sozinho na primeira subida
[2025-03-11 15:12] Camila Rocha: testei do zero aqui, subiu sem erro
