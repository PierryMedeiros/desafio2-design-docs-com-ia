# ADR potencial: Interface CanalLembrete, cadeia de fallback entre canais e canal único global por .env

**Módulo**: LEMBRETES
**Categoria**: Arquitetura / Padrão de projeto
**Prioridade**: Documentar obrigatoriamente (Pontuação: 120; infraestrutura crítica de domínio, base 70 por julgamento)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR formal existe. Relacionada às duas decisões de canal (WhatsApp, SMS).

---

## O que foi identificado

Em 2020-03-10, ao decidir adotar SMS, Rafael Lima determinou que o job não chamaria o Twilio diretamente: criou-se a interface `CanalLembrete` (`850b301`, 2020-03-10) com `enviar($paciente, $mensagem)`, e o e-mail virou apenas uma implementação. Helena pediu "deixa preparado" para o WhatsApp. Em 2023-09 isso se confirmou: o WhatsApp entrou como "mais uma implementação" e o job quase não mudou ("a interface de 2020 se pagou", Juliana, Slack 2023-09-12).

Em `16d93d2` a interface ganhou `nome()` e `aceita(Paciente)`, e foram criados `CanalComFallback` (tenta cada canal que aceita o paciente na ordem; lança `FalhaNoEnvioDoLembrete` se todos falharem, o que aciona o retry do job), `FabricaDeCanais` (ordem em `config('lembretes.canais')` = `['whatsapp','sms']`) e `LogCanal` (decorador de desenvolvimento, introduzido como canal `log` em `8e6ba6a`).

Decisão correlata, tomada de forma deliberadamente provisória em 2020-03-10: "por enquanto simples: um canal pra todo mundo, configurado no .env" (`ecca671`). A configuração continua global, não por clínica.

## Por que isto merece um ADR

- **Impacto**: ponto de extensão para novos canais; define quem decide o canal (infra, não clínica) e a semântica de fallback (qualquer exceção do canal leva ao próximo, inclusive erro após o envio já aceito pelo provedor, risco de duplicidade).
- **Trade-offs**: simplicidade versus configuração por clínica; ordem de canais fixa em config.
- **"Sempre foi assim"**: o canal global por `.env` não tem decisão formal de manter; foi um "por enquanto" de 2020 que persiste.
- **Estabilidade**: padrão há 6 anos, sem mudanças de contrato desde 2023-09.

## Evidências encontradas

### Arquivos-chave
- [`app/Lembretes/CanalLembrete.php`](../../../../../app/Lembretes/CanalLembrete.php)
- [`app/Lembretes/CanalComFallback.php`](../../../../../app/Lembretes/CanalComFallback.php)
- [`app/Lembretes/FabricaDeCanais.php`](../../../../../app/Lembretes/FabricaDeCanais.php)
- [`config/lembretes.php`](../../../../../config/lembretes.php)

### Evidência de código
```php
foreach ($this->canais as $canal) {
    if (! $canal->aceita($paciente)) { continue; }
    try { $canal->enviar($paciente, $mensagem); return; }
    catch (Throwable $e) { /* log e tenta o próximo */ }
}
throw new FalhaNoEnvioDoLembrete(...);
```

### Análise de impacto (git)
- Interface: `850b301` (2020-03-10); config em env: `ecca671` (2020-03-16); fallback: `16d93d2` (2023-09-12); testes: `4c4d6a8`, `0e38e8f`.
- Canal de e-mail (`EmailCanal`) existiu como implementação e foi removido em `5b4aca3`.

## Questões a responder no ADR

- Por que o canal é global e não configurável por clínica? Quando revisitar?
- Falha depois do envio aceito pelo provedor deve ou não cair para o próximo canal?
- Como adicionar um novo canal (checklist)?

## ADRs potenciais relacionados
- `whatsapp-cloud-api-direta-como-canal-principal.md`
- `sms-twilio-como-canal-e-fim-do-email-como-canal-unico.md`
- `politica-de-idempotencia-e-retry-do-envio-de-lembretes.md`
