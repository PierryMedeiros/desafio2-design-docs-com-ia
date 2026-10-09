# ADR Potencial: API REST versionada na URL (`/api/v1`) para o app do paciente

**Módulo**: API
**Categoria**: Arquitetura (protocolo/estilo de API)
**Prioridade**: Must Document (Score: 145)
**Data de Identificação**: 2026-10-09

---

## Contexto de ADRs Existentes

Nenhum ADR formal existe em `docs/adrs/` (apenas `mapping.md`). Decisão relacionada, identificada neste mesmo lote: [API embutida no monólito, sem GraphQL nem serviço separado](./api-embutida-no-monolito-em-vez-de-graphql-e-servico-separado.md).

---

## O Que Foi Identificado

A API do app do paciente é REST, com a versão na URL (`/api/v1/...`), definida em `routes/api.php` dentro de `Route::prefix('v1')`. O motivo, registrado no Slack #arquitetura em 2021-02-17, é que app de loja não pode ter atualização forçada: há paciente que fica meses com a versão antiga instalada. Por isso o contrato da v1 não pode quebrar, e uma mudança grande viraria `/api/v2`, com a v1 viva até o parque de apps atualizar. Marcos Teixeira perguntou se versão em header não seria mais "correto"; Beatriz Nogueira respondeu que a URL é mais fácil de enxergar no log e de rotear, e que no app basta trocar a base URL. A decisão foi fechada por Rafael Lima na mesma conversa ("REST, /api/v1, Sanctum com um token por dispositivo").

O histórico git confirma a implementação em março de 2021 (`e75f608` login, `c1f87bc` horários livres, `5d1b376` agendamentos, `b267361` cancelamento, `4f09b9c` testes da v1), com `/api/v1` em produção em 2021-03-24 segundo o Slack. A regra de "não quebrar contrato" foi realmente cumprida: em 2023-08-01 Beatriz registrou que o app 3.0 continua em `/api/v1`, sem precisar de v2, e a v2 nunca foi criada. As evoluções posteriores foram aditivas: paginação (`1cd87ff`, 2021-07), dados de profissional e serviço no recurso (`5da94bb`, 2021-10), reagendamento via campo opcional `reagendar_de` no `POST /agendamentos` (`09c6423`, 2024-01), em vez de rota ou versão nova.

Há também convenções de contrato que emergiram de correções e hoje fazem parte do que o app espera: `409` quando o horário foi ocupado (`7be0a67`, 2021-04, que trocou o `422` anterior e introduziu transação com `lockForUpdate` no profissional), `422` para credenciais inválidas e horário fora da agenda, `404` (e não `403`) ao cancelar agendamento de outro paciente (`c982411`, 2024-10), data no passado rejeitada na busca de horários (`9b91cf8`, 2025-06), e `AgendamentoResource` como formato único de resposta. Essas convenções foram tratadas aqui como parte da decisão de contrato (consolidação), e não como ADRs separados.

## Por Que Isto Pode Merecer um ADR

- **Impacto**: o contrato é consumido por um binário que a empresa não controla (app nas lojas, fora do repositório). Qualquer mudança de resposta pode quebrar pacientes reais.
- **Trade-offs**: versão na URL (visível e fácil de rotear) contra versão em header (considerada "mais correta"); manter v1 indefinidamente contra custo de evoluir só de forma aditiva.
- **Complexidade**: baixa no código, alta na disciplina exigida: não há mecanismo automático que impeça quebra de contrato além dos testes em `tests/Feature/Api/`.
- **Conhecimento do time**: quem altera `AgendamentoResource` ou os códigos de status precisa saber que a v1 é congelada de fato. Hoje isso só está no Slack.
- **Implicações futuras**: nenhuma política de depreciação da v1 está registrada; a doc do contrato vivia num workspace do Postman (rascunho em 2021-02-22; link do README quebrado em 2022-01-19, corrigido por Marcos) e não há OpenAPI/Postman versionado no repositório. Se o workspace for perdido, o contrato só existe nos testes e no código.
- **Contexto temporal**: estável há mais de 5 anos (2021-03 a 2025-06).

## Evidências Encontradas no Código

### Arquivos-chave
- [`routes/api.php`](../../../../../routes/api.php) - prefixo `v1`, rotas públicas (`/auth/token`) e rotas protegidas por `auth:sanctum` + `tenant`.
- [`app/Http/Controllers/Api/V1/`](../../../../../app/Http/Controllers/Api/V1/) - namespace `V1` explícito, preparado para coexistir com uma futura `V2`.
- [`app/Http/Resources/AgendamentoResource.php`](../../../../../app/Http/Resources/AgendamentoResource.php) - formato de resposta estável.
- [`app/Http/Kernel.php`](../../../../../app/Http/Kernel.php) e [`app/Providers/RouteServiceProvider.php`](../../../../../app/Providers/RouteServiceProvider.php) - grupo `api` com `ThrottleRequests:api` (60 req/min por usuário ou IP, valor padrão do Laravel; configuração isolada, não candidata a ADR).
- `tests/Feature/Api/AgendamentosTest.php`, `HorariosTest.php`, `AuthTokenTest.php` - guardam o contrato.

### Evidência de Código
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [AuthController::class, 'store']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('/horarios', [HorarioController::class, 'index']);
        Route::get('/agendamentos', [AgendamentoController::class, 'index']);
        Route::post('/agendamentos', [AgendamentoController::class, 'store']);
        Route::delete('/agendamentos/{id}', [AgendamentoController::class, 'destroy'])->whereNumber('id');
    });
});
```

### Análise de Impacto
- Introduzido: 2021-03-04 (`e75f608`, primeira rota `v1`), produção em 2021-03-24.
- Modificado: dezenas de commits `feat(api)`/`fix(api)` de 2021 a 2025, todos compatíveis com a v1.
- Última mudança relevante: 2025-06-10 (`9b91cf8`, validação de data no passado).
- Afeta: 6 arquivos de código + testes; app mobile externo.
- Temas recentes: reagendamento, correções de horários livres, escopo por paciente.

### Alternativas (observáveis)
- Versão em header: sugerida por Marcos Teixeira e descartada (Slack, 2021-02-17).
- GraphQL: planejada em `docs/ARQUITETURA.md` (2019), nunca implementada (ver ADR potencial relacionado).

## Questões a Responder no ADR (se criado)

- Qual é a política de compatibilidade da v1 e quando seria justificável criar a v2?
- Existe plano de depreciação? Qual a versão mínima do app ainda ativa?
- Onde deve morar a especificação do contrato (OpenAPI no repositório?) agora que o workspace do Postman foi perdido de vista?
- Os códigos `409`, `422` e `404` são contrato oficial ou acidente de implementação?

## ADRs Potenciais Relacionados
- [API embutida no monólito, sem GraphQL nem serviço separado](./api-embutida-no-monolito-em-vez-de-graphql-e-servico-separado.md)
- [Sanctum com token opaco por dispositivo](./sanctum-token-opaco-por-dispositivo-em-vez-de-jwt.md)

## Notas Adicionais
- Alguns assuntos de contrato não foram promovidos a ADR por serem granulares (Red Flag 5): paginação de 20 itens (`1cd87ff`), limite de 60 req/min, formato de data `Y-m-d H:i`.
- O Slack cita um PR de push notification do app que "toca só na API" (2021-11-03), mas não há rota ou código de push no repositório atual; pode ter sido descartado ou ficar fora desta branch. Sem evidência, não foi tratado como decisão.
