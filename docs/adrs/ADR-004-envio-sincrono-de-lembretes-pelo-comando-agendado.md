# ADR-004: Envio síncrono de lembretes pelo comando agendado

- **Status:** Superseded
- **Data:** 2019-06-03
- **Decisores:** Juliana Prado (desenvolvedora), Rafael Lima (CTO)
- **Relações:**
  - superseded by [ADR-005: Fila Redis com worker dedicado para os lembretes](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md)

## Contexto e problema

O MVP incluía "lembrete do atendimento para o paciente por e-mail". No kickoff ficou só que os lembretes seriam um "comando agendado no cron (detalhes com a Juliana)" (`contexto/atas/2019-04-02-kickoff-tecnico.md`). Faltava decidir como o comando entregaria os lembretes. Na época eram três clínicas.

## Opções consideradas

1. **Enviar dentro do próprio comando, de forma síncrona**, um e-mail por vez pelo SMTP.
2. **Needs Input:** as fontes não registram outra opção avaliada em 2019. O "por enquanto" do Rafael sugere que um envio assíncrono (fila) era conhecido, mas não há registro de que tenha sido avaliado nesse momento.

## Decisão

Opção 1. O comando `lembretes:enviar`, agendado no `app/Console/Kernel.php` a cada 10 minutos, passa por todas as clínicas, busca os agendamentos das próximas 24 horas sem lembrete, envia o e-mail na hora pelo SMTP configurado e grava `lembrete_enviado_em` (`2187466`, `docs/ARQUITETURA.md`). O motivo está no Slack:

> [2019-06-03 15:40] Juliana Prado: lembrete por e-mail pronto. comando `lembretes:enviar` no cron a cada 10 min, pega quem tem atendimento nas próximas 24h e manda
> [2019-06-03 15:42] Rafael Lima: boa. por enquanto síncrono mesmo, são 3 clínicas

## Consequências

### Positivas

- Simples: um comando, sem fila, worker ou serviço adicional, coerente com o prazo do MVP.
- Com três clínicas, o comando rodava em cerca de 20 segundos (Juliana, Slack 2020-02-11).

### Negativas

- O tempo do comando cresce com o número de clínicas e de agendamentos e depende da velocidade do SMTP. Em 2020-02-10 ele levou 14 minutos, mais que o intervalo de 10 minutos do cron.
- Sem trava contra sobreposição, duas execuções pegavam os mesmos agendamentos, e pacientes receberam o lembrete duas ou três vezes (`contexto/slack/arquitetura.md`, 2020-02-11).

## Substituição

Substituída pela [ADR-005](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md) em 2020-02-12. O comando `lembretes:enviar` foi removido em `d79772d` (2020-02-17) e trocado por `lembretes:enfileirar`.

## Evidências

- Commits: `2187466` (lembrete de consulta por e-mail), `4115d91` (não manda lembrete para agendamento cancelado), `136dc02` (testes do comando), `d79772d` (remoção).
- Arquivos: `app/Console/Commands/EnviarLembretes.php` (existe no histórico até `d79772d`), `app/Console/Kernel.php`, `database/migrations/2019_06_04_151000_add_lembrete_enviado_em_to_agendamentos_table.php`.
- Rastros: `contexto/slack/arquitetura.md` (2019-06-03 e 2020-02-11), `contexto/atas/2019-04-02-kickoff-tecnico.md` (seção 2), `docs/ARQUITETURA.md` (seção "Lembretes").
