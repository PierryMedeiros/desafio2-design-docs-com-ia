# ADR potencial: WhatsApp Cloud API (Meta) direta como canal principal de lembretes, com opt-in

**Módulo**: LEMBRETES
**Categoria**: Tecnologia / Integração externa
**Prioridade**: Documentar obrigatoriamente (Pontuação: 125; Etapa 0, infraestrutura crítica de domínio: mensageria ao paciente)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR formal existe. Relacionada a `interface-canallembrete-com-cadeia-de-fallback.md` e `sms-twilio-como-canal-e-fim-do-email-como-canal-unico.md`.

---

## O que foi identificado

Em 2023-09 o WhatsApp passou a ser o canal principal dos lembretes em todas as clínicas, com SMS como fallback. Commits: canal pela Cloud API (`174d1ba`, 2023-09-05), opt-in no cadastro do paciente (`e1dc55f`, 2023-09-06), canal principal com fallback (`16d93d2`, 2023-09-12) e teste do fallback (`0e38e8f`). O anúncio do CTO (Slack #geral, 2023-09-12) registra: piloto com 40 clínicas em julho e agosto, faltas de 18% para 11%; SMS é caro por mensagem e lido muito menos.

Duas escolhas dentro da decisão ficaram registradas no Slack: (1) integração **direta com a Cloud API da Meta** e não via BSP, porque os dois BSPs avaliados cobram por mensagem sobre o que a Meta já cobra e seriam mais um intermediário guardando dado de paciente; a Cloud API exige template aprovado e uma chamada HTTP por lembrete; (2) envio **somente a pacientes com `aceita_whatsapp`**; sem aceite, ou em falha, vai SMS.

## Por que isto merece um ADR

- **Impacto**: dependência de plataforma externa (Meta) para a função que reduz faltas, objetivo central do produto; template aprovado externamente (`WHATSAPP_TEMPLATE`), versão da API fixa (`WHATSAPP_API_VERSION=v18.0`) e token em env.
- **Trade-offs**: sem BSP, o time assume diretamente mudanças de versão da Graph API, aprovação de templates e limites da conta; ganho em custo e menos terceiros com dado de paciente (LGPD).
- **Opt-in**: `aceita_whatsapp` no painel; no app só "na próxima versão" (Slack 2023-09-12). Não está verificado no repo se o app já cobre.
- **Mensagem como parâmetro único do template**: o texto inteiro vai como um parâmetro `body`, o que acopla o texto montado no job ao template aprovado (a investigar).
- **Futuro**: alternativa de múltiplos números/templates por clínica não existe; credenciais são globais.

## Evidências encontradas

### Arquivos-chave
- [`app/Lembretes/Canais/WhatsAppCloud.php`](../../../../../app/Lembretes/Canais/WhatsAppCloud.php)
- [`config/lembretes.php`](../../../../../config/lembretes.php) - bloco `whatsapp`.
- [`app/Http/Controllers/PacienteController.php`](../../../../../app/Http/Controllers/PacienteController.php) - validação e gravação de `aceita_whatsapp`.

### Evidência de código
```php
public function aceita(Paciente $paciente): bool
{
    return $paciente->aceita_whatsapp && ! empty($paciente->telefone);
}
```

### Análise de impacto (git)
- Introduzido: 2023-09-05 a 2023-09-12 (`174d1ba`, `e1dc55f`, `16d93d2`, `0e38e8f`).
- Sem alterações relevantes no canal desde então (nos commits do módulo).
- Alternativas registradas: ficar só no SMS; BSP.

## Questões a responder no ADR

- Qual a base legal/fluxo do opt-in (LGPD) e como é revogado?
- Quem renova token e aprova novos templates? O que acontece se a Meta reprovar o template?
- Qual a estratégia para upgrade da versão da Graph API?

## ADRs potenciais relacionados
- `interface-canallembrete-com-cadeia-de-fallback.md`
- `sms-twilio-como-canal-e-fim-do-email-como-canal-unico.md`

## Notas adicionais
Os dados do piloto (18% para 11%) vêm de mensagem de chat do CTO, não de relatório. O relatório de faltas (`da7e7f6`, `c9c0c54`, 2024-02) pode servir de validação posterior.
