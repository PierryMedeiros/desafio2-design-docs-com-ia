> Export de página do Notion (espaço "Engenharia", página "Reuniões 2022 / Multi-tenancy"), gerado em 2026-09-28 por Camila Rocha antes do cancelamento da assinatura. Blocos de comentários e menções foram convertidos em texto simples.

# Ata: reunião sobre o modelo de multi-tenancy

- **Data:** 2022-05-20 (sexta-feira), 10h às 12h15
- **Formato:** call (Google Meet)
- **Participantes:** Rafael Lima, Juliana Prado, Marcos Teixeira, Thiago Fonseca, Beatriz Nogueira
- **Redação:** Marcos Teixeira

## Contexto

Reunião para cumprir a ação "Avaliar o modelo de multi-tenancy (responsável: Rafael)" do postmortem do deploy de 12/04/2022, em que o `tenants:migrate` levou 5h30 para passar pelos 380 schemas e clínicas ficaram com erro 500 enquanto seus schemas não eram migrados.

Rafael lembrou os dois problemas que qualquer opção precisa resolver:

1. o tempo de migration cresce com o número de clínicas (hoje 380, a previsão comercial é passar de 600 até o fim de 2023);
2. durante o deploy existe um estado misto, com parte das clínicas no schema novo e parte no antigo.

Também foi lido o e-mail da Paula Mendes (DPO) de 03/05/2022, em resposta à consulta sobre unificar os dados das clínicas.

## Opções avaliadas

### (a) Manter um schema por clínica, rodando as migrations em paralelo por schema

Juliana apresentou um protótipo: o `tenants:migrate` dispararia um job por schema e vários workers processariam em paralelo. Em teste com cópia do banco, 8 processos em paralelo reduziriam o tempo de 5h30 para algo perto de 50 minutos.

**Descartada.** Só reduz o tempo; não muda a natureza do problema. O tempo continua crescendo com o número de clínicas, o deploy continua tendo estado misto (por menos tempo, mas tem) e o paralelismo é limitado pela carga que o banco aguenta durante o horário de uso. Thiago lembrou que índices em tabelas grandes em paralelo podem derrubar o desempenho para todos.

### (b) Um banco por clínica

Levantada pelo Thiago por completude.

**Descartada.** Piora o problema de migração (seriam 380 bancos para migrar em vez de 380 schemas, com conexão separada para cada um) e complica bastante o custo e a gestão de conexões: cada instância da aplicação e o worker precisariam abrir conexões para muitos bancos, e o limite de conexões do RDS já é uma preocupação hoje.

### (c) Schema único com Row Level Security (RLS) do Postgres

Beatriz trouxe a opção: todas as tabelas com coluna de clínica e políticas de RLS no banco filtrando as linhas pela clínica da sessão.

**Descartada**, por dois motivos:

- conflita com o pool de conexões: para a política funcionar, seria preciso setar uma variável de sessão com a clínica a cada request (ou a cada transação) e garantir que ela seja limpa. Com conexões reaproveitadas, existe o risco de uma requisição herdar a variável da anterior e enxergar dados de outra clínica, que é justamente o que queremos evitar. Os jobs do worker teriam o mesmo cuidado;
- ninguém do time domina RLS. Erros nas políticas são difíceis de perceber em testes e o time não se sentiu seguro em operar isso em produção agora.

Rafael registrou que a ideia não é ruim em si e pode ser revisitada se o time ganhar experiência, mas não como base da mudança agora.

### (d) Schema único com coluna `tenant_id` e escopo global na aplicação

Proposta do Rafael e do Marcos:

- todas as tabelas de clínica passam a ter `tenant_id`;
- uma trait nos models (`BelongsToTenant`) aplica um escopo global que filtra por `tenant_id` e preenche o campo na criação;
- um middleware identifica a clínica do usuário logado (ou do token, na API) e guarda o tenant atual para a requisição; os jobs carregam o `tenant_id` no payload;
- o comando `tenants:migrate` deixa de existir; volta a ser `php artisan migrate` normal.

**Escolhida.** A migration passa a rodar uma vez só, independentemente do número de clínicas, e o deploy deixa de ter estado misto: ou a tabela tem a coluna nova para todo mundo, ou não tem para ninguém.

Riscos discutidos:

- uma consulta que fuja do escopo global (SQL cru, `DB::table`, `withoutGlobalScopes`) pode trazer dados de outra clínica. Mitigação: testes de isolamento entre clínicas na suíte de testes e revisão obrigatória de qualquer uso de `withoutGlobalScopes` ou `DB::table`;
- a separação física deixa de existir, o que aumenta o peso dos dados sensíveis em texto claro. Mitigação: condição abaixo.

Efeito colateral bem-vindo, citado pelo Thiago: com um schema só, o número de conexões e o volume de metadados caem, e provavelmente dá para usar uma instância de RDS menor depois da migração. **Não foi motivo da escolha**; Rafael pediu para não vender a mudança internamente como economia, porque o motivo é o deploy.

## Condição para a migração

A unificação só começa depois que estiver em produção a criptografia de campos pedida pela DPO no e-mail de 03/05:

- `cpf` e `notas_clinicas` com o cast `encrypted` do Laravel;
- coluna `cpf_hash` (HMAC-SHA256 com chave própria) para a busca por CPF;
- dados existentes convertidos em todos os schemas.

Os casts e o `cpf_hash` já estão em produção desde 17/05 (Rafael e Juliana). Falta confirmar que o comando de conversão rodou em todos os schemas sem pendência.

## Responsáveis e próximos passos

| Item | Responsável | Prazo |
|------|-------------|-------|
| Confirmar a conversão dos dados existentes (`cpf` e `notas_clinicas`) em todos os schemas | Juliana Prado | 2022-05-27 |
| Trait `BelongsToTenant`, escopo global e middleware de identificação da clínica | Rafael Lima | 2022-06-10 |
| Migrations com `tenant_id` nas tabelas da clínica, no schema único | Marcos Teixeira | 2022-06-10 |
| Ajustar API `/api/v1` e jobs para carregar o tenant (token e payload) | Beatriz Nogueira | 2022-06-10 |
| Comando único para copiar os dados de todos os schemas `clinica_<slug>` para o schema único, preenchendo `tenant_id`, com verificação de contagem por tabela | Rafael Lima | 2022-06-14 |
| Testes de isolamento entre clínicas (criar duas clínicas e garantir que nenhuma rota, consulta ou job de uma enxergue dados da outra) | Marcos Teixeira e Beatriz Nogueira | 2022-06-14 |
| Ensaio da migração completa em cópia do banco de produção, com tempo medido | Thiago Fonseca | 2022-06-17 |
| Janela de migração em produção (sábado, fora do horário comercial) e plano de volta | Thiago Fonseca e Rafael Lima | a definir após o ensaio (previsão: fim de junho) |
| Avisar a Paula para atualizar o RIPD | Rafael Lima | após a data definida |

Próxima reunião de acompanhamento: sexta, 03/06/2022, 10h.
