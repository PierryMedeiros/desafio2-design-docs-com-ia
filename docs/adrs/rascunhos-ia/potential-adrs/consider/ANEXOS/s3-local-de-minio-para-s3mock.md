# Potencial ADR: Emulador de S3 no ambiente local (MinIO substituído por Adobe S3Mock)

**Módulo**: ANEXOS (com ponte para INFRA)
**Categoria**: Tecnologia / Desenvolvimento
**Prioridade**: Consider (Score: 76)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes
Nenhum ADR existente.

---

## O que foi identificado

Quando o S3 entrou, o ambiente de desenvolvimento ganhou um serviço MinIO no `docker-compose.yml` (`72295a3`, 2021-08-10, Thiago Fonseca; o Slack cita "minio no compose de dev"), mais um contêiner auxiliar `s3-bucket` (`minio/mc`) que criava o bucket `horalis-anexos`, com `AWS_USE_PATH_STYLE_ENDPOINT=true` e credenciais locais fixas. Em 2025-07-08 (`23ac85a`, "bucket criado na subida do s3 local") o MinIO foi trocado por `adobe/s3mock:4.7.0`, que cria o bucket via variável (`COM_ADOBE_TESTING_S3MOCK_STORE_INITIAL_BUCKETS`) e removeu o contêiner auxiliar e o volume; a porta mudou de 9000 para 9090. O commit não explica o motivo da troca (apenas o ganho de simplicidade é visível: -21 linhas de compose). O motivo é **desconhecido**; possível hipótese a confirmar com Thiago Fonseca: mudança de licenciamento/imagens do MinIO. Não afirmar sem confirmação.

O `endpoint_publico` de `config/filesystems.php` existe por causa desse emulador: o navegador acessa `localhost:9090`, o contêiner `s3:9090`. `989d7cc` (2025-11-18) fixou as tags das imagens.

## Por que pode merecer um ADR
- Paridade dev/prod do armazenamento, com particularidades (path style, endpoint público) que quebram quem sobe o projeto pela primeira vez.
- Troca recente, sem justificativa registrada, e substituída uma decisão antiga (2021).
- Pontuação baixa: custo de mudança de poucas semanas, afeta só dev e testes (os testes usam `Storage::fake`).

## Evidências
- [`docker-compose.yml`](../../../../../docker-compose.yml), serviço `s3`
- [`config/filesystems.php`](../../../../../config/filesystems.php), `endpoint_publico`
- Commits: `72295a3`, `23ac85a`, `989d7cc`

## Perguntas
- Por que sair do MinIO? O S3Mock cobre `temporaryUrl` com o mesmo comportamento da AWS?

## Relacionados
- [Armazenamento em S3](../../must-document/ANEXOS/armazenamento-de-anexos-em-s3.md)
- [URL pré-assinada](../../must-document/ANEXOS/download-por-url-temporaria-pre-assinada.md)
