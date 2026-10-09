# ADR-010: Anexos no S3 com download por URL pré-assinada

- **Status:** Accepted
- **Data:** 2021-08-10
- **Decisores:** Rafael Lima (CTO), Thiago Fonseca, Juliana Prado
- **Relações:**
  - depends on [ADR-009: Migração da infraestrutura para a AWS](ADR-009-migracao-da-infraestrutura-para-a-aws.md)
  - relates to [ADR-011: Sessões do painel no Redis](ADR-011-sessoes-do-painel-no-redis.md)

## Contexto e problema

Desde 2019, os exames e documentos enviados pela recepção ficavam no disco local do servidor, em `storage/app/anexos`, e o download passava pelo Laravel (`33e9bfb`, `docs/ARQUITETURA.md`). Em 2021-08-09, Thiago subiu a segunda instância da aplicação atrás do balanceador. No dia seguinte, a Clínica Movimento disse que "o anexo sumiu": o exame enviado de manhã dava 404, uma vez sim e outra não. O upload tinha caído numa instância e o download na outra (`contexto/slack/arquitetura.md`, 2021-08-10). A segunda instância foi retirada até resolver.

A pergunta era onde guardar os anexos para que qualquer instância os servisse, sem expor dados de saúde.

## Opções consideradas

1. **Object storage S3, com download por URL pré-assinada (`temporaryUrl`) que expira em poucos minutos.**
2. **Manter o disco local e voltar a uma instância só.** Foi o estado provisório escolhido no dia do incidente.
3. **Needs Input:** as fontes não registram outras alternativas avaliadas, como disco compartilhado entre instâncias (NFS/EFS) ou download pelo Laravel lendo do S3 (proxy).

## Decisão

Opção 1. Rafael: "anexos vão pro S3. download por URL pré-assinada (`temporaryUrl`), expirando em poucos minutos. o arquivo nunca fica público" (Slack, 2021-08-10). O motivo explícito é tirar o arquivo do disco da instância, para poder rodar mais de uma instância, sem tornar os arquivos públicos.

No mesmo movimento, a sessão foi para o Redis. Essa é uma decisão separada, na [ADR-011](ADR-011-sessoes-do-painel-no-redis.md).

Implementação:

- **Bucket (Thiago):** bloqueio de acesso público, criptografia e uma role para a aplicação só com put/get. Pronto em produção em 2021-08-12. Essa configuração está só no Slack, porque não há infraestrutura como código no repositório.
- `72295a3`: disco `s3` e um S3 local no compose. Era um minio na época; hoje é `adobe/s3mock` (`23ac85a`).
- `1eec607`: `app/Anexos/ArmazenamentoAnexos.php`, com URL temporária de 10 minutos.
- `220dd2e`: comando `anexos:migrar-para-s3`, que copiou uns 41 mil arquivos e foi removido depois de usado em `d7a7363`.
- `6ac7756`: content-type dos anexos.

Em 2021-08-18, as duas instâncias voltaram ao balanceador. Os arquivos locais foram apagados em 2021-08-19, depois de conferidas as contagens.

## Consequências

### Positivas

- Qualquer instância grava e serve qualquer anexo, e a aplicação pode rodar com várias instâncias atrás do balanceador.
- O arquivo nunca é público. O link de download vale 10 minutos (`VALIDADE_MINUTOS = 10`), e o bucket bloqueia acesso público.
- O download sai direto do S3 e não ocupa PHP-FPM.

### Negativas

- Mais uma dependência externa, com credenciais e configuração de bucket mantidas fora do repositório.
- Quem tiver a URL consegue baixar o arquivo enquanto ela vale, sem login.
- O desenvolvimento local precisa de um S3 simulado e de um endpoint público separado (`AWS_PUBLIC_ENDPOINT`, `endpoint_publico` em `config/filesystems.php`).

## Evidências

- Commits: `72295a3`, `1eec607`, `220dd2e`, `6ac7756`, `e1f518b` (testes com storage fake), `d7a7363` (remove o comando de migração), `23ac85a` (bucket criado na subida do S3 local).
- Arquivos: `app/Anexos/ArmazenamentoAnexos.php`, `app/Http/Controllers/AnexoController.php`, `config/filesystems.php`, `docker-compose.yml` (serviço `s3`).
- Rastros: `contexto/slack/arquitetura.md` (2021-08-10 a 2021-08-19), `docs/ARQUITETURA.md` (seção "Anexos", estado de 2019).
