# Postmortem: deploy travado na migration de 12/04/2022

- **Autor:** Thiago Fonseca
- **Data do documento:** 2022-04-14
- **Data do incidente:** 2022-04-12 (terça-feira)
- **Revisado por:** Rafael Lima, Juliana Prado
- **Status:** ações em andamento

## Resumo

Na terça-feira, 12/04/2022, a release 2.14 (campo de convênio no agendamento) foi publicada em horário comercial. A release exigia uma coluna nova, `convenio`, na tabela `agendamentos` de todos os schemas de clínica. O comando `php artisan tenants:migrate`, que aplica as migrations de `database/migrations/tenant` schema por schema, precisou passar por 380 schemas e levou cerca de 5h30 para terminar.

Durante esse intervalo, schemas já migrados e schemas ainda não migrados conviveram com o código novo no ar. As clínicas cujos schemas ainda não tinham a coluna receberam erro 500 no painel e no app do paciente até a migration chegar nelas.

## Impacto

- Duração total: das 10h12 às 15h44 (aprox. 5h30).
- Clínicas afetadas: todas as 380 ficaram com erro em algum momento, porque o código novo foi para as duas instâncias antes da migration começar. O tempo de indisponibilidade de cada clínica dependeu da posição do schema na ordem de execução (ordem alfabética do slug): as primeiras ficaram poucos minutos fora, as últimas mais de cinco horas.
- Painel (Blade): erro 500 na agenda, na tela de novo agendamento e na ficha do paciente para clínicas não migradas.
- App do paciente: erro 500 em `GET /api/v1/agendamentos` e no fluxo de marcação para as mesmas clínicas.
- Lembretes: jobs da fila `notificacoes` falharam para clínicas não migradas. Parte foi reprocessada à tarde; estimamos cerca de 1.900 lembretes que saíram com atraso e cerca de 300 que não saíram (horário do atendimento já tinha passado quando reprocessamos).
- Suporte: 112 chamados abertos no dia; a Helena fez contato direto com as 15 maiores contas.
- Não houve perda nem vazamento de dados.

## Contexto da infraestrutura

- Postgres gerenciado na AWS (RDS), uma instância, com um schema por clínica (`clinica_<slug>`).
- Duas instâncias da aplicação Laravel atrás de um balanceador.
- Um contêiner de worker para as filas.
- Redis gerenciado (ElastiCache) para fila e sessões.
- Sentry para erros.
- O deploy é feito pela pipeline: atualiza o código nas duas instâncias, roda `php artisan tenants:migrate` e reinicia o worker.

## Linha do tempo (12/04/2022, horário de Brasília)

| Horário | Evento |
|---------|--------|
| 10h05 | Rafael aprova o merge da release 2.14 e dispara o deploy. |
| 10h09 | Código novo ativo nas duas instâncias. |
| 10h12 | `tenants:migrate` começa pelo primeiro schema. |
| 10h14 | Sentry abre alerta de `SQLSTATE[42703]: Undefined column: 7 ERROR: column "convenio" does not exist` em volume alto. |
| 10h20 | Thiago confirma que o comando está rodando e que cada schema leva perto de 50 segundos (a migration cria a coluna e um índice em `agendamentos`, que é grande nas clínicas antigas). |
| 10h31 | Primeiros chamados de suporte. Helena avisa no #geral. |
| 10h40 | Discussão sobre rollback. Os schemas já migrados funcionavam com o código novo; desfazer exigiria rodar o `down` em cada schema migrado, com o mesmo problema de tempo. Decidido seguir em frente. |
| 11h05 | Juliana tenta abrir uma segunda execução do comando para acelerar; interrompida em seguida por risco de dois processos pegarem o mesmo schema. Ficou só a execução original. |
| 11h30 | Helena começa contato com as maiores contas e passa a informar previsão por letra do nome da clínica. |
| 12h50 | Aprox. 140 schemas migrados. Rafael publica estimativa de término às 15h45. |
| 13h20 | Marcos pausa o worker para parar de acumular falhas de lembretes das clínicas não migradas. |
| 15h44 | Último schema migrado. Erros 500 cessam no Sentry. |
| 15h50 | Worker religado; jobs falhos reenviados com `queue:retry`. |
| 16h30 | Verificação manual por amostragem em 20 clínicas. Incidente encerrado. |

## Causa raiz

A combinação de três fatores:

1. **Migration por schema, em sequência.** O `tenants:migrate` percorre os schemas um a um. O tempo total é a soma do tempo de cada schema, e cresce com o número de clínicas. Com 380 clínicas e uma migration que cria índice em tabela grande, o deploy passou de 5 horas. Em 2020, com cerca de 60 clínicas, uma migration parecida levava uns 10 minutos, e ninguém percebeu a curva.
2. **Código novo publicado antes de todos os schemas estarem migrados.** A pipeline troca o código primeiro e migra depois. Com um schema só, isso passa despercebido em segundos; com centenas, abre uma janela longa em que parte das clínicas está num estado e parte no outro.
3. **Release em horário comercial.** O deploy foi disparado às 10h de uma terça, horário de pico de uso pela recepção das clínicas.

Fator contribuinte: o comando mostra só a barra de progresso, sem tempo por schema nem estimativa. Só descobrimos o tempo por schema olhando o log manualmente.

## O que funcionou

- O Sentry detectou o problema em dois minutos e agrupou os erros por mensagem, o que deixou claro que era a coluna faltando.
- A decisão de não fazer rollback foi correta: teria custado o mesmo tempo e deixado o sistema no mesmo estado misto.
- A comunicação da Helena com as clínicas maiores evitou cancelamentos (nenhum registrado até a data deste documento).
- Os jobs de lembrete ficaram no Redis e puderam ser reprocessados.

## O que não funcionou

- Não havia como saber, antes do deploy, quanto tempo a migration levaria no conjunto de schemas.
- A tentativa de paralelizar na mão quase causou um problema maior.
- Não existe procedimento para pausar o worker durante deploy com migration.

## Ações

| Ação | Responsável | Prazo |
|------|-------------|-------|
| Avaliar o modelo de multi-tenancy (responsável: Rafael) | Rafael Lima | 2022-05-20 |
| Janela de deploy fora do horário comercial | Thiago Fonseca | imediato |
| Registrar o tempo de cada schema no log do `tenants:migrate` | Juliana Prado | 2022-04-22 |
| Pausar o worker automaticamente durante deploys com migration de tenant | Marcos Teixeira | 2022-04-29 |
| Alerta no Sentry quando um mesmo erro passar de 500 eventos em 5 minutos | Thiago Fonseca | 2022-04-22 |
| Modelo de comunicado para clínicas em incidentes longos | Helena Duarte | 2022-04-29 |

Enquanto a avaliação do modelo de multi-tenancy não for concluída, migrations que alteram tabelas grandes devem ser revisadas pelo Rafael antes do merge e só entram em deploys feitos depois das 20h.
