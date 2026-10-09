# Potencial ADR: Armazenar anexos de agendamento em S3 (bucket privado)

**Módulo**: ANEXOS
**Categoria**: Tecnologia / Infraestrutura
**Prioridade**: Must Document (Score: 145)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR existente em `docs/adrs/generated/` (diretório inexistente). Decisão relacionada: [Anexos em disco local (substituída)](../../consider/ANEXOS/anexos-em-disco-local-substituido-por-s3.md), que esta decisão supersede.

---

## O que foi identificado

Os anexos (exames e documentos que a recepção sobe no agendamento) são gravados no serviço de armazenamento de objetos S3 da AWS, por meio do disco `s3` do Laravel/Flysystem (`league/flysystem-aws-s3-v3`). A classe `ArmazenamentoAnexos` concentra a escrita (`Storage::disk('s3')->put`) e a geração de URL de leitura. O banco guarda apenas metadados (`caminho`, `nome_original`, `tipo`, `tamanho`).

A decisão nasceu de um incidente. Em 2021-08-10, um dia depois de subir a segunda instância da aplicação atrás do balanceador, a Clínica Movimento relatou que "o anexo sumiu" (404 intermitente). O Slack #arquitetura (2021-08-10 10:45) explica: o upload caía numa instância e o download na outra, porque o arquivo ficava no disco local de cada servidor. Rafael Lima decidiu no mesmo dia (11:05) que "anexos vão pro S3", com bucket privado; Thiago Fonseca ficou com o bucket (bloqueio de acesso público, criptografia, role da aplicação só com put/get). No git, o disco S3 e o serviço local entraram em `72295a3` (2021-08-10) e o código de upload/download em `1eec607` (2021-08-11). Cerca de 41 mil arquivos foram copiados até 2021-08-18 e os locais apagados em 2021-08-19, segundo o Slack.

A decisão é estável há mais de 5 anos: o disco `s3` nunca foi trocado, só recebeu ajustes de compatibilidade (`6ac7756`, `d83fcc2`).

## Por que isto pode merecer um ADR

- **Impacto**: viabilizou rodar mais de uma instância (junto com a sessão em Redis, mesmo incidente) e é a única dependência de armazenamento de arquivos do sistema.
- **Trade-offs**: dependência de AWS e de credenciais (`AWS_*`); latência de rede no upload (arquivo passa pelo PHP e depois segue ao S3, `$arquivo->get()` carrega o arquivo inteiro em memória, limite de 10 MB); ambiente local precisa de emulador (ver ADR de S3 local).
- **Complexidade**: média; bucket, role IAM, criptografia e bloqueio de acesso público ficam **fora do repositório** e só estão descritos no Slack.
- **Conhecimento do time**: quem mexer em infraestrutura ou em dados de saúde precisa saber que existe um bucket privado com política específica.
- **Futuro**: há dado de saúde (exames) em armazenamento de objetos; retenção, versionamento, backup e política de ciclo de vida do bucket não aparecem em nenhum registro.
- **Contexto temporal**: estável desde agosto de 2021.

## Evidências encontradas no código

### Arquivos-chave
- [`app/Anexos/ArmazenamentoAnexos.php`](../../../../../app/Anexos/ArmazenamentoAnexos.php) - `DISCO = 's3'`, `guardar()` e `urlTemporaria()`
- [`config/filesystems.php`](../../../../../config/filesystems.php) - disco `s3` com `throw => true`, `endpoint` e `endpoint_publico` configuráveis
- [`app/Http/Controllers/AnexoController.php`](../../../../../app/Http/Controllers/AnexoController.php) - `store` e `show`
- [`tests/Feature/Painel/AnexoTest.php`](../../../../../tests/Feature/Painel/AnexoTest.php) - usa `Storage::fake('s3')`
- [`docker-compose.yml`](../../../../../docker-compose.yml) - serviço `s3` local

### Evidência de código
```php
// app/Anexos/ArmazenamentoAnexos.php
public const DISCO = 's3';
Storage::disk(self::DISCO)->put($caminho, $arquivo->get(), [
    'ContentType' => $arquivo->getMimeType(),
]);
```

### Análise de impacto (git e contexto)
- Introduzido: 2021-08-10 (`72295a3`, disco S3 e serviço local no compose) e 2021-08-11 (`1eec607`, "anexos no s3 com url temporária")
- Migração dos dados legados: `220dd2e` (2021-08-12, comando `anexos:migrar-para-s3`), removido depois de executado em `d7a7363` (2022-08-09)
- Ajustes: `6ac7756` (2021-08-24, content-type), `e1f518b` (2021-09-14, teste com storage fake), `d83fcc2` (2022-09-15, Flysystem 3 e `Storage::build`)
- Gatilho: incidente de 404 intermitente com duas instâncias (Slack #arquitetura, 2021-08-10)
- Afeta: 1 módulo no código, mas é dependência de infraestrutura (AWS, compose, `.env`)

### Alternativas (observáveis)
- Manter disco local com volume compartilhado entre instâncias (ex.: NFS/EFS): **não há registro** de ter sido considerado.
- Servir sempre pelo Laravel, em vez de URL assinada: era o comportamento anterior (ver ADR de URL temporária).
- O Slack não registra outras alternativas de storage (apenas "anexos vão pro S3").

## Perguntas a responder no ADR

- Por que S3 e não um sistema de arquivos compartilhado?
- Quais são as configurações obrigatórias do bucket (bloqueio de acesso público, criptografia, role mínima put/get)? Onde estão versionadas?
- Há política de retenção, versionamento, backup e exclusão (LGPD) para os anexos?
- Os anexos são considerados dado sensível (exames) e entram no escopo do RIPD/DPO? O e-mail da DPO (2022-05-03) trata criptografia de CPF e notas clínicas, mas não cita os anexos.

## Potenciais ADRs relacionados
- [URL temporária pré-assinada](./download-por-url-temporaria-pre-assinada.md)
- [Chaves por tenant](../../consider/ANEXOS/chaves-de-objeto-por-tenant-e-uuid.md)
- [Disco local (superseded)](../../consider/ANEXOS/anexos-em-disco-local-substituido-por-s3.md)
- [S3 local](../../consider/ANEXOS/s3-local-de-minio-para-s3mock.md)

## Notas adicionais

- A sessão em Redis foi decidida no mesmo momento e pelo mesmo incidente; pertence ao módulo AUTH/INFRA e deve ser referenciada, não duplicada, aqui.
- Não há ADR sobre a configuração da role/bucket porque ela não está no repositório. Candidato a confirmar com Thiago Fonseca ou com quem hoje administra a AWS.
