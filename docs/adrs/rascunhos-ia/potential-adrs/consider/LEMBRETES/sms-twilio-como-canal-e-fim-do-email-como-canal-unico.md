# ADR potencial: SMS via Twilio (2020) e aposentadoria do canal de e-mail (2023)

**Módulo**: LEMBRETES
**Categoria**: Tecnologia / Decisão histórica (parcialmente superada)
**Prioridade**: Considerar (Pontuação estimada: 85 por julgamento; decisão histórica, o SMS hoje é fallback e o e-mail foi removido)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR formal existe. Sucedida em parte por `whatsapp-cloud-api-direta-como-canal-principal.md`.

---

## O que foi identificado

**Lembrete por e-mail (2019).** O MVP previa lembrete por e-mail (ata de kickoff 2019-04-02, "SMTP do provedor da VPS por enquanto"), implementado em `2187466` (2019-06-04), e `docs/ARQUITETURA.md` (jul/2019) ainda descreve apenas esse modelo.

**SMS (2020).** Em 2020-03-10 Helena relatou que 6 clínicas diziam que o paciente não lê e-mail e que a Odonto Vila Mariana medira faltas de 22% em fevereiro. Alternativas discutidas: manter e-mail com texto/horário melhores (24h e 2h antes; rejeitada: "não é questão de horário"), WhatsApp (adiado por burocracia), SMS (escolhido). Provedor: Twilio, "não vou inventar nada", API simples, número brasileiro. Commits: `8aad430` (canal SMS, 2020-03-12); em produção em 3 clínicas em 2020-03-16; correção de telefone sem DDD `13eb367`.

**Fim do e-mail (2023).** `EmailCanal` foi removido em `5b4aca3` (2023-12-05, Juliana Prado, sem corpo de commit). **Nenhum motivo está registrado** em Slack, atas ou commit; provável consequência do WhatsApp+SMS cobrirem os pacientes, mas é inferência. O Mailhog e o SMTP de dev já tinham saído do compose.

## Por que isto merece um ADR

- Explica por que o produto usa duas integrações pagas por mensagem e por que o e-mail não é mais opção, evitando que alguém "reative o e-mail por ser grátis".
- Registra o evento que gerou a interface de canais.
- Twilio segue como dependência (credenciais `TWILIO_SID/TOKEN/FROM`) e já causou o incidente de 2024-03 (ver política de retry).

## Evidências encontradas

### Arquivos-chave
- [`app/Lembretes/Canais/SmsTwilio.php`](../../../../../app/Lembretes/Canais/SmsTwilio.php), [`config/lembretes.php`](../../../../../config/lembretes.php) (bloco `sms`).
- Código removido: `app/Lembretes/Canais/EmailCanal.php` (apagado em `5b4aca3`), `app/Mail/LembreteConsulta.php` e `resources/views/emails/lembrete.blade.php` (introduzidos em `2187466`).
- [`docs/ARQUITETURA.md`](../../../../../docs/ARQUITETURA.md) - descreve o estado de 2019 (desatualizado).

### Análise de impacto (git)
- E-mail: `2187466` (2019-06-04) até `5b4aca3` (2023-12-05), ~4,5 anos.
- SMS: `8aad430` (2020-03-12), `ecca671`, `13eb367` (2020-09-09).

## Questões a responder no ADR

- Por que o e-mail foi removido (custo de manutenção, deliverability, ausência de uso)?
- Houve alternativa a Twilio avaliada? Quanto custa o SMS por mensagem (Marcos prometeu planilha, não registrada)?
- Qual a política de fallback quando o SMS também falha?

## ADRs potenciais relacionados
- `whatsapp-cloud-api-direta-como-canal-principal.md`
- `interface-canallembrete-com-cadeia-de-fallback.md`
- `fila-redis-worker-dedicado-para-lembretes.md` (substituiu o envio síncrono por e-mail)
