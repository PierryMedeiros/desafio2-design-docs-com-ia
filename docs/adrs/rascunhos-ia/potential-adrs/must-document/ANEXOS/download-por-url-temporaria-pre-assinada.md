# Potencial ADR: Entregar anexos por redirecionamento para URL pré-assinada de 10 minutos

**Módulo**: ANEXOS
**Categoria**: Segurança / Arquitetura
**Prioridade**: Must Document (Score: 120)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR existente. Relaciona-se com [Armazenamento em S3](./armazenamento-de-anexos-em-s3.md) e supersede parcialmente o download via Laravel descrito em [Anexos em disco local](../../consider/ANEXOS/anexos-em-disco-local-substituido-por-s3.md).

---

## O que foi identificado

O download de um anexo não passa mais pelo PHP. `AnexoController::show` busca o `Anexo` (já filtrado pelo escopo de tenant e pela autenticação do painel) e responde com `redirect()->away()` para uma URL pré-assinada (`temporaryUrl`) do S3 com validade de 10 minutos (`VALIDADE_MINUTOS = 10`). O arquivo "nunca fica público" (Rafael Lima, Slack #arquitetura, 2021-08-10 11:05).

Antes (2019, `33e9bfb`) o controller entregava o arquivo com `Storage::disk('anexos')->download($caminho, $nome_original)`, e `docs/ARQUITETURA.md` descreve isso: "O download passa pelo Laravel (`GET /anexos/{id}`), que confere o login antes de entregar o arquivo". A mudança foi feita em `1eec607` (2021-08-11). Há também uma peça específica: `endpoint_publico` (`AWS_PUBLIC_ENDPOINT`) permite assinar a URL com um host diferente do endpoint interno, porque no ambiente local o contêiner da aplicação enxerga `http://s3:...` mas o navegador precisa de `http://localhost:...`.

## Por que isto pode merecer um ADR

- **Impacto**: muda o modelo de segurança do dado de saúde. A autorização acontece só na emissão da URL; quem obtiver o link, dentro de 10 minutos, baixa o arquivo sem sessão.
- **Trade-offs**: tira carga do PHP e do nginx; em troca, a URL vaza por histórico de navegador, logs de proxy e compartilhamento. O download deixou de enviar `nome_original` (o `download()` anterior definia o nome; o redirect não define `ResponseContentDisposition`), então o arquivo baixa com o nome do objeto (UUID). Isso é uma consequência visível no código, sem registro de decisão.
- **Complexidade**: baixa no código, mas o par `endpoint`/`endpoint_publico` é um detalhe fácil de quebrar.
- **Conhecimento do time**: necessário para qualquer mudança em controle de acesso a anexos e para quem avalia auditoria LGPD.
- **Futuro**: nenhuma auditoria de acesso ao download é registrada (apenas o log de requisição); o valor "10 minutos" no código difere do "poucos minutos" falado no Slack e não tem justificativa registrada.
- **Estabilidade**: inalterado desde 2021-08.

## Evidências encontradas no código

### Arquivos-chave
- [`app/Anexos/ArmazenamentoAnexos.php`](../../../../../app/Anexos/ArmazenamentoAnexos.php) - `urlTemporaria()` e `discoParaUrls()`
- [`app/Http/Controllers/AnexoController.php`](../../../../../app/Http/Controllers/AnexoController.php) - `show()`
- [`config/filesystems.php`](../../../../../config/filesystems.php) - `endpoint_publico`
- [`tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`](../../../../../tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php) - garante 404 para anexo de outra clínica

### Evidência de código
```php
// app/Http/Controllers/AnexoController.php
return redirect()->away($armazenamento->urlTemporaria($anexo));
```

### Análise de impacto
- Introduzido: `1eec607` (2021-08-11); `endpoint_publico` adicionado na mesma data
- Relacionados: `6ac7756` (2021-08-24, ContentType no upload, para o navegador abrir PDF/imagem corretamente), `d83fcc2` (2022-09-15, `createS3Driver` trocado por `Storage::build`)
- Estado anterior: `33e9bfb` (2019-06-07), download proxy pelo Laravel
- Afeta: 2 arquivos de código + configuração

### Alternativas (observáveis)
- Download proxy pelo Laravel (estado até 2021-08-11): descartado de fato; sem justificativa escrita além de "o arquivo nunca fica público".
- CloudFront/CDN assinado: sem registro.

## Perguntas a responder no ADR

- Por que 10 minutos? O que acontece com links abertos em e-mail/WhatsApp?
- Aceita-se que a autorização seja verificada só na emissão da URL?
- A perda do nome original no download é intencional?
- Como o ambiente local gera URLs que o navegador alcança (`endpoint_publico`)?

## Potenciais ADRs relacionados
- [Armazenamento em S3](./armazenamento-de-anexos-em-s3.md)
- [Chaves de objeto por tenant](../../consider/ANEXOS/chaves-de-objeto-por-tenant-e-uuid.md)

## Notas adicionais
O isolamento entre clínicas no download depende do escopo global de tenant no model `Anexo` (adicionado em `396964c`, 2022-06-07), não da URL. Referenciar o ADR de TENANCY.
