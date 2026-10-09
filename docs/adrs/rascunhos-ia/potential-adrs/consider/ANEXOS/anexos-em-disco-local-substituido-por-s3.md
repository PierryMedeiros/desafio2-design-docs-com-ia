# Potencial ADR: Anexos em disco local do servidor (decisão de 2019, substituída por S3)

**Módulo**: ANEXOS
**Categoria**: Arquitetura (decisão substituída)
**Prioridade**: Consider (Score: 85)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR existente. Esta decisão é **superseded** por [Armazenamento em S3](../../must-document/ANEXOS/armazenamento-de-anexos-em-s3.md). Registrar como ADR histórico com status "Substituído".

---

## O que foi identificado

Em 2019-06-07 (`33e9bfb`, "upload de anexos no agendamento", Juliana Prado) os anexos passaram a ser gravados em um disco `anexos` do tipo `local`, com raiz em `storage/app/anexos`, organizados por `agendamentos/{id}`. O download passava pelo Laravel, com conferência de login. O contexto era o MVP em uma única VPS (`docs/ARQUITETURA.md`: "Produção roda numa única VPS"), com 3 clínicas parceiras. As tabelas de anexo viviam em `database/migrations/tenant`, uma por schema de clínica.

Funcionou enquanto havia uma só instância. A decisão deixou de valer em 2021-08-10, quando a segunda instância foi colocada atrás do balanceador (pós migração para AWS em 2021-06) e passou a produzir 404 intermitente. O disco `anexos` e seu código foram removidos (`1eec607`, e a entrada de config em `d7a7363`, 2022-08-09). O trecho de `docs/ARQUITETURA.md` que descreve esse desenho continua desatualizado no repositório.

## Por que isto pode merecer um ADR

- **Impacto**: registra a premissa implícita "servidor único" que o sistema carregou por 2 anos e que quebrou com o scale-out.
- **Trade-offs da época**: simples, sem custo, sem dependência externa; acoplava dado ao servidor.
- **Conhecimento do time**: evita repetir o erro com outros estados locais (sessão em arquivo falhou no mesmo incidente).
- **Lição**: o postmortem de 2022-04-12 trata de outro tema (migrations), mas ambos mostram decisões de MVP que não acompanharam a escala (380 clínicas em 2022).

## Evidências encontradas

### Arquivos-chave
- `docs/ARQUITETURA.md`, seção "Anexos" (estado de 2019)
- Histórico: `33e9bfb` (criação), `1eec607` (substituição), `d7a7363` (remoção do disco `anexos` da config)

### Evidência de código (removida)
```php
// 33e9bfb - AnexoController
$caminho = $arquivo->store('agendamentos/'.$agendamento->id, 'anexos');
return Storage::disk('anexos')->download($anexo->caminho, $anexo->nome_original);
```

### Análise de impacto
- Vigente: 2019-06-07 a 2021-08-10 (cerca de 26 meses)
- Substituição e migração: cerca de 41 mil arquivos copiados e conferidos entre 2021-08-12 e 2021-08-19 (Slack); o comando `anexos:migrar-para-s3` (`220dd2e`) era executado uma única vez, percorria todos os tenants (schemas), copiava arquivo a arquivo e atualizava `caminho`; foi apagado em `d7a7363`.
- Observação: o comando e o prefixo `tenants/{id}` na chave provavelmente existiam porque cada clínica tinha seu schema e ids de agendamento colidiam entre clínicas (inferência).

### Alternativas
Não há registro de alternativas em 2019 (a ata de kickoff de 2019-04-02 não menciona anexos).

## Perguntas a responder no ADR

- Em 2019 havia previsão de mais de um servidor? (`ARQUITETURA.md` previa microsserviços em 2020.)
- Qual foi a estratégia de migração e rollback? Ela foi testada com `--dry-run`?

## Potenciais ADRs relacionados
- [Armazenamento em S3](../../must-document/ANEXOS/armazenamento-de-anexos-em-s3.md)
- [Chaves por tenant](./chaves-de-objeto-por-tenant-e-uuid.md)

## Notas adicionais
Pontuação reduzida por ser decisão já substituída; vale como ADR de histórico e como correção do `docs/ARQUITETURA.md`.
