# ADR-006: Lembretes por SMS com Twilio, atrás da interface CanalLembrete

- **Status:** Accepted
- **Data:** 2020-03-10
- **Decisores:** Rafael Lima (CTO), Helena Duarte (CEO), Marcos Teixeira, Juliana Prado
- **Relações:**
  - amended by [ADR-014: WhatsApp Cloud API como canal principal, com fallback para SMS](ADR-014-whatsapp-cloud-api-como-canal-principal-com-fallback-para-sms.md)
  - depends on [ADR-005: Fila Redis com worker dedicado para os lembretes](ADR-005-fila-redis-com-worker-dedicado-para-lembretes.md)

## Contexto e problema

Em março de 2020, Helena conversou com seis clínicas, e todas disseram a mesma coisa: "paciente não lê e-mail". A Odonto Vila Mariana mediu faltas de 22% em fevereiro. As clínicas pediam SMS, e muitas pediam WhatsApp. Até então o lembrete saía só por e-mail (`contexto/slack/arquitetura.md`, 2020-03-10).

## Opções consideradas

1. **Continuar só no e-mail, melhorando o texto e o horário** (por exemplo, mandar 24h antes e de novo 2h antes), proposta do Marcos.
2. **WhatsApp.** Juliana: "whatsapp oficial hoje é bem burocrático".
3. **SMS por um provedor externo (Twilio).** Juliana: "sms dá pra fazer rápido".

## Decisão

Opção 3: lembretes por SMS pelo Twilio. Rafael: "provedor externo, não vou inventar nada. olhei o Twilio, API simples e tem número brasileiro".

- **Opção 1 descartada:** Helena e Rafael concordaram que ficar só no e-mail não resolve as faltas, porque "o paciente não abre o e-mail, não é questão de horário".
- **Opção 2 adiada:** era burocrática na época. Helena pediu que o sistema ficasse preparado para ela ("um dia vai ter WhatsApp, deixa preparado").

Para isso, o job não chama o Twilio direto. Rafael criou a interface `CanalLembrete`, com `enviar($paciente, $mensagem)`. O e-mail passou a ser uma implementação (`EmailCanal`) e o SMS outra (`SmsTwilio`). O canal é um só para todas as clínicas, escolhido no `.env` ("por enquanto simples: um canal pra todo mundo, configurado no .env").

Implementação:

- `850b301`: interface `CanalLembrete` e `EmailCanal`.
- `8aad430`: `SmsTwilio` e `config/lembretes.php`.
- `ecca671`: `LEMBRETES_CANAL=sms` passa a ser o padrão (antes era `email`).

Entrou em produção nas três parceiras em 2020-03-16 e nas demais na semana seguinte.

## Consequências

### Positivas

- O SMS virou o canal dos lembretes para todas as clínicas.
- A interface permitiu trocar e somar canais sem mexer no job. Em 2023, o WhatsApp entrou como mais uma implementação "quase sem mexer no job" (Marcos), e Juliana comentou: "a interface de 2020 se pagou" (`contexto/slack/geral.md`, 2023-09-12).
- Um canal de log para desenvolvimento entrou como mais uma implementação (`8e6ba6a`), e os canais ganharam testes isolados (`4c4d6a8`).

### Negativas

- Custo por mensagem. Helena perguntou o preço, e Marcos respondeu "uns centavos". Em 2023, Rafael lembrou que o SMS "é caro por mensagem" (`contexto/slack/geral.md`).
- Dependência de um provedor externo e do telefone do paciente bem cadastrado. Telefone sem DDD foi corrigido em `13eb367`.
- Um canal global, sem escolha por clínica ou por paciente. O "por enquanto" de 2020 continuou assim. Em 2023 a ordem dos canais passou a ser fixa em `config/lembretes.php`.
- Com o padrão em `sms`, o `EmailCanal` ficou sem uso. Ele foi apagado em `5b4aca3` (2023-12-05), sem discussão registrada. Por ser remoção de código morto, não ganhou ADR própria (veja o [índice](README.md)).

## Emenda

A [ADR-014](ADR-014-whatsapp-cloud-api-como-canal-principal-com-fallback-para-sms.md) (2023-09-12) emenda esta decisão: o WhatsApp passa a ser o canal principal, e o SMS pelo Twilio fica como fallback. A interface `CanalLembrete` continua e ganhou `aceita()` e a cadeia `CanalComFallback`.

## Evidências

- Commits: `850b301`, `8aad430`, `ecca671`, `4c4d6a8`, `8e6ba6a`, `13eb367`, `5b4aca3` (remoção do `EmailCanal`).
- Arquivos: `app/Lembretes/CanalLembrete.php`, `app/Lembretes/Canais/SmsTwilio.php`, `config/lembretes.php`, `app/Lembretes/Canais/EmailCanal.php` (existe no histórico até `5b4aca3`).
- Rastros: `contexto/slack/arquitetura.md` (2020-03-10 a 2020-03-16), `contexto/slack/geral.md` (2023-09-12).
