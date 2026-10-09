# ADR-014: WhatsApp Cloud API como canal principal dos lembretes, com fallback para SMS

- **Status:** Accepted
- **Data:** 2023-09-12
- **Decisores:** Rafael Lima (CTO), Juliana Prado, Marcos Teixeira, Beatriz Nogueira
- **Relações:**
  - amends [ADR-006: Lembretes por SMS com Twilio, atrás da interface CanalLembrete](ADR-006-lembretes-por-sms-com-twilio-atras-da-interface-canallembrete.md)

## Contexto e problema

Desde 2020 os lembretes saíam por SMS, pelo Twilio ([ADR-006](ADR-006-lembretes-por-sms-com-twilio-atras-da-interface-canallembrete.md)). O WhatsApp tinha sido adiado por ser "bem burocrático", mas as clínicas pediam por ele desde aquela época, e Helena pedia que o sistema ficasse preparado.

Em 2023, um piloto com 40 clínicas em julho e agosto testou o WhatsApp. Rafael anunciou o resultado em 2023-09-12: as faltas caíram de 18% para 11% (`contexto/slack/geral.md`). Explicando por que não ficar só no SMS, ele disse que "o SMS é o 18% do piloto. é caro por mensagem e paciente lê muito menos que whatsapp".

## Opções consideradas

1. **Continuar só no SMS.**
2. **WhatsApp por um BSP** (provedor intermediário de soluções de negócio). Thiago lembrou que esse é "o caminho mais comum".
3. **WhatsApp direto pela Cloud API da Meta, com SMS como fallback.**

## Decisão

Opção 3. A partir de 2023-09-12, o WhatsApp é o canal principal dos lembretes em todas as clínicas, e o SMS fica de fallback. O WhatsApp só vai para paciente com `aceita_whatsapp` marcado. Sem aceite, ou se a mensagem falhar, sai SMS como antes (Rafael, `contexto/slack/geral.md`, 2023-09-12).

Por que as outras foram descartadas:

- **Só SMS:** custa mais por mensagem e é menos lido. As faltas ficaram em 18% com SMS contra 11% com WhatsApp no piloto (Rafael).
- **BSP:** o time olhou dois BSPs. Eles "cobram por mensagem em cima do que a meta já cobra, e seria mais um intermediário guardando dado de paciente" (Juliana). Rafael completou que "a Cloud API direta ficou simples: template aprovado no gerenciador da Meta e uma chamada HTTP por lembrete".

O WhatsApp entrou como mais uma implementação de `CanalLembrete`, com uma cadeia que tenta o WhatsApp e cai para o SMS, "quase sem mexer no job" (Marcos). Implementação:

- `174d1ba`: canal `WhatsAppCloud`.
- `e1dc55f`: opt-in `aceita_whatsapp` no cadastro do paciente.
- `16d93d2`: `CanalComFallback`, `FabricaDeCanais` e `canais => ['whatsapp', 'sms']` em `config/lembretes.php`.
- `0e38e8f`: testes do fallback.

## Consequências

### Positivas

- Queda de faltas no piloto, de 18% para 11%, segundo o anúncio. Os números vêm de uma mensagem de chat, e o relatório do piloto não está nas fontes.
- Custo por mensagem menor que o do SMS para a maioria dos lembretes, e nenhum intermediário extra guardando dado de paciente.
- O SMS continua cobrindo quem não deu aceite ou não tem WhatsApp, e cobre falhas do WhatsApp.
- A interface de 2020 absorveu o canal novo sem mudar o job.

### Negativas

- Dependência da Meta: template aprovado no gerenciador, versão da Graph API (`versao_api`, `v18.0` por padrão) e credenciais próprias.
- A ordem dos canais é fixa e global (`config/lembretes.php`). Não há escolha por clínica.
- O aceite (`aceita_whatsapp`) fica no cadastro do painel. No app ele só entraria "na próxima versão" (Beatriz).
- Se o WhatsApp falhar depois de já ter entregue (por exemplo, por timeout), o paciente pode receber também o SMS. O risco está no `docs/HLD.md`.

## Evidências

- Commits: `174d1ba`, `e1dc55f`, `16d93d2`, `0e38e8f`.
- Arquivos: `app/Lembretes/Canais/WhatsAppCloud.php`, `app/Lembretes/CanalComFallback.php`, `app/Lembretes/FabricaDeCanais.php`, `config/lembretes.php`, `database/migrations/2023_09_06_104000_add_aceita_whatsapp_to_pacientes_table.php`.
- Rastros: `contexto/slack/geral.md` (2023-09-12), `contexto/slack/arquitetura.md` (2020-03-10).
