# Potencial ADR: Chave de objeto `tenants/{tenant_id}/agendamentos/{id}/{uuid}.ext`

**Módulo**: ANEXOS
**Categoria**: Arquitetura / Segurança
**Prioridade**: Consider (Score: 80)
**Data de identificação**: 2026-10-09

---

## Contexto de ADRs existentes

Nenhum ADR existente. Depende de [Armazenamento em S3](../../must-document/ANEXOS/armazenamento-de-anexos-em-s3.md) e do módulo TENANCY.

---

## O que foi identificado

O caminho do objeto no bucket é montado em `ArmazenamentoAnexos::guardar()` como `tenants/{tenant_id}/agendamentos/{agendamento_id}/{uuid}.{extensão}`. O nome original do arquivo não faz parte da chave (fica só na coluna `nome_original`), a extensão vem do nome enviado pelo cliente (`bin` se vazia), e o `tenant_id` é gravado também em `anexos`.

O prefixo `tenants/{id}` já existia em `1eec607` (2021-08-11), quando o sistema ainda usava um schema por clínica; ali ele provavelmente servia para evitar colisão (inferência, não registrada) de `agendamento_id` entre schemas. Depois da unificação para schema único com `tenant_id` (`396964c` em 2022-06-07, `08fab1a` em 2022-06-09, `827b1b7` em 2022-06-28), a mesma chave passou a ser coerente com o novo modelo e `Anexo` ganhou `BelongsToTenant`, sem migrar objetos. Ou seja, a estrutura do bucket sobreviveu à troca do modelo de tenancy.

## Por que isto pode merecer um ADR

- **Impacto**: a chave é contrato com o bucket (políticas IAM por prefixo, migração, exclusão por clínica, LGPD).
- **Trade-offs**: UUID evita adivinhação e colisão; em contrapartida, não há deduplicação e o nome legível só existe no banco. Prefixar por tenant facilita remover os dados de uma clínica, mas a aplicação usa uma role única para todo o bucket.
- **Futuro**: custo de mudar é alto, pois exige copiar objetos e atualizar `caminho`. Exclusão de anexo e do agendamento não remove o objeto (o `onDelete('cascade')` na tabela só apaga a linha), possível objeto órfão. Verificar antes de afirmar no ADR.

## Evidências

- [`app/Anexos/ArmazenamentoAnexos.php`](../../../../../app/Anexos/ArmazenamentoAnexos.php), `guardar()`
- [`app/Models/Anexo.php`](../../../../../app/Models/Anexo.php), `use BelongsToTenant`
- `tests/Feature/Tenancy/IsolamentoEntreClinicasTest.php`, teste `test_usuario_nao_altera_nem_baixa_anexo_de_outra_clinica`

```php
$caminho = sprintf('tenants/%d/agendamentos/%d/%s.%s', $agendamento->tenant_id, $agendamento->id, Str::uuid(), $arquivo->getClientOriginalExtension() ?: 'bin');
```

### Análise de impacto
- Introduzido: `1eec607` (2021-08-11); coluna `tenant_id` na tabela via `08fab1a` (2022-06-09)
- Sem alteração de formato desde então

### Alternativas
Anteriormente `agendamentos/{id}/{hash}` (`33e9bfb`, 2019). Nada além disso registrado.

## Perguntas a responder no ADR
- Por que prefixar por tenant se a role não restringe por prefixo?
- Qual o procedimento para excluir os dados de uma clínica ou de um paciente?
- Objetos órfãos são limpos?

## Notas adicionais
Possível consolidação com o ADR de S3, caso a equipe prefira um único registro.
