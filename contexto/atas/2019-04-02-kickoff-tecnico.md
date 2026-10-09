> Export de documento do Google Docs ("Atas 2019 / Kickoff técnico"), gerado em 2026-09-28 por Camila Rocha. O documento original foi escrito pela Juliana Prado no dia da reunião. Comentários laterais não foram exportados.

# Ata: kickoff técnico do Horalis

- **Data:** 2019-04-02 (terça-feira), 14h às 16h10
- **Local:** sala de reunião do coworking (Rua Fradique Coutinho)
- **Participantes:** Helena Duarte (CEO), Rafael Lima (CTO), Juliana Prado (desenvolvedora)
- **Redação:** Juliana Prado

## Pauta

1. Objetivo e prazo do MVP
2. Stack e forma de construir o sistema
3. Dados das clínicas e LGPD
4. Divisão de responsabilidades
5. Próximos passos

## 1. Objetivo e prazo do MVP

Helena apresentou o escopo combinado com as três clínicas parceiras (Clínica Movimento, Odonto Vila Mariana e Clínica Integrar):

- agenda por profissional, com bloqueio de horários;
- cadastro de pacientes;
- marcação, remarcação e cancelamento pela recepção;
- lembrete do atendimento para o paciente por e-mail;
- relatório simples de atendimentos do mês.

Prazo: **três meses**, com as clínicas parceiras usando em produção no começo de julho. Helena reforçou que o prazo é inegociável porque a primeira mensalidade das parceiras começa a contar em julho.

Ficaram fora do MVP: app para o paciente, pagamento online, prontuário completo e integração com convênios.

## 2. Stack e forma de construir o sistema

**Decidido:** um monólito em **PHP com Laravel**, com as telas renderizadas no servidor em **Blade**. Sem front-end separado.

Motivos registrados:

- o time (Rafael e Juliana) domina PHP e Laravel e já entregou projetos assim;
- com três meses de prazo, não há espaço para aprender stack nova nem para manter dois projetos (API + front);
- as telas do MVP são formulários e listagens, sem necessidade de interface muito dinâmica.

**Alternativa considerada:** back-end em Node com uma SPA em React.
**Descartada** porque ninguém do time tem experiência real com essa combinação e o prazo do MVP não comporta a curva de aprendizado. Rafael comentou que pode fazer sentido rever no futuro se houver app ou front mais rico, mas não agora.

Outros pontos de stack:

- Banco: Postgres (o Rafael já deixou configurado)
- Hospedagem: uma VPS contratada pelo Rafael; ele cuida do servidor e dos backups.
- Envio de e-mail: SMTP do provedor da VPS por enquanto.
- Lembretes: comando agendado no cron (detalhes com a Juliana).
- Repositório no GitHub, branch `main` protegida, revisão de código entre Rafael e Juliana.

## 3. Dados das clínicas e LGPD

Helena trouxe a orientação do advogado da empresa (Dr. Otávio, escritório que nos atende desde a abertura do CNPJ). Como o sistema vai guardar dados de saúde de pacientes, que a LGPD trata como dados sensíveis, ele recomendou que os dados de cada clínica fiquem isolados uns dos outros da forma mais forte que for viável, para que um erro de programação não exponha pacientes de uma clínica para outra.

**Decidido:** isolamento por **um schema por clínica** no banco. Cada clínica ganha seu próprio schema (`clinica_<slug>`) com as mesmas tabelas. A aplicação identifica a clínica pelo usuário logado e aponta para o schema certo em cada requisição.

**Alternativa considerada:** banco único com uma coluna `clinica_id` em cada tabela, filtrando por ela nas consultas.
**Descartada** pelo risco de vazamento de dados entre clínicas: basta uma consulta sem o filtro para mostrar dados de outra clínica. Com schemas separados, uma consulta esquecida não enxerga outra clínica.

Rafael observou que vai ser preciso um jeito de rodar as migrations em todos os schemas e de criar o schema quando uma clínica nova entrar. Fica com ele.

Helena pediu que o texto de termos de uso e a política de privacidade sejam revisados pelo advogado antes de julho.

## 4. Divisão de responsabilidades

| Pessoa | Responsabilidade no MVP |
|--------|-------------------------|
| Rafael Lima | estrutura do projeto, servidor, banco, isolamento por clínica, autenticação |
| Juliana Prado | agenda, cadastro de pacientes, marcação e lembretes por e-mail |
| Helena Duarte | contato com clínicas parceiras, validação das telas, textos e termos de uso |

Revisão semanal com as clínicas parceiras às sextas, a partir de 26/04, conduzida pela Helena com protótipo navegável.

## 5. Próximos passos

- [ ] Rafael: criar repositório, projeto Laravel e ambiente na VPS até 05/04.
- [ ] Rafael: middleware de identificação da clínica e comando para migrar todos os schemas até 10/05.
- [ ] Juliana: rascunho das telas de agenda em Blade até 10/04.
- [ ] Juliana: modelo de dados de agenda e paciente até 12/04.
- [ ] Helena: lista de campos obrigatórios do cadastro de paciente, validada com as parceiras, até 09/04.
- [ ] Helena: agendar conversa com o advogado sobre termos de uso.

Próxima reunião de acompanhamento: terça, 09/04, 14h.
