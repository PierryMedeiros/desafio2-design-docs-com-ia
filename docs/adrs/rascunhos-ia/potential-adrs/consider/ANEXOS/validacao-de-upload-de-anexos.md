# Potencial ADR: Política de validação de upload (10 MB, PDF/JPG/PNG/TXT, sem varredura)

**Módulo**: ANEXOS
**Categoria**: Segurança
**Prioridade**: Consider (Score: 76)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes
Nenhum ADR existente.

---

## O que foi identificado

O upload é validado no controller: `required|file|max:10240|mimes:pdf,jpg,jpeg,png,txt`. A regra existe sem mudanças desde a primeira versão (`33e9bfb`, 2019-06-07) e atravessou a migração para S3 e o upgrade do Laravel. Não há varredura de vírus, limite de quantidade por agendamento, nem cota por clínica. A extensão gravada no objeto vem do nome informado pelo cliente, e o `ContentType` vem do MIME detectado (`6ac7756`, 2021-08-24).

## Por que isto pode merecer um ADR

- **Impacto**: define quais arquivos de terceiros (recepção) entram no sistema e são servidos de volta a profissionais de saúde.
- **Trade-offs**: simples e suficiente para exames em PDF/imagem; sem antivírus e sem tratamento de formatos como DICOM, DOCX ou arquivos acima de 10 MB (pode ser uma dor real para exames de imagem).
- **Dúvida de motivo**: nada no contexto explica por que 10 MB nem por que esses tipos ("sempre foi assim" é plausível, segundo `contexto/LEIA-ME.md`).

Pontuação baixa: a decisão é pequena (1 arquivo) e pode ser considerada regra de produto; só vale ADR se a equipe quiser registrar a postura de segurança.

## Evidências
- [`app/Http/Controllers/AnexoController.php`](../../../../../app/Http/Controllers/AnexoController.php), linha da validação `max:10240`
- `33e9bfb` (criação), `1eec607` (refatoração sem alterar a regra)

## Perguntas a responder
- O limite de 10 MB vem de requisito de clínica ou do `upload_max_filesize` do PHP/nginx?
- Há necessidade de antivírus/varredura?

## Relacionados
- [Armazenamento em S3](../../must-document/ANEXOS/armazenamento-de-anexos-em-s3.md)
