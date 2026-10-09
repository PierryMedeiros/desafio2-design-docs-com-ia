> Export de thread da caixa rafael.lima@horalis.example (conta mantida em arquivo após o desligamento), gerado em 2026-09-30 por Camila Rocha. Anexos e assinaturas automáticas removidos.

---

**De:** Paula Mendes <paula.mendes@privacidade-consultoria.example>
**Para:** Rafael Lima <rafael.lima@horalis.example>, Helena Duarte <helena.duarte@horalis.example>
**Data:** 2022-05-03 (terça-feira), 17:42
**Assunto:** Re: Consulta sobre unificação da base de dados das clínicas

Rafael e Helena, boa tarde.

Obrigada pela explicação na call de ontem e pelo resumo que vocês mandaram depois. Seguem minhas considerações por escrito, como combinamos, para ficar registrado.

Entendi que a equipe está avaliando deixar de separar cada clínica no seu próprio espaço dentro do banco e passar a guardar os dados de todas as clínicas juntos, identificando a qual clínica cada registro pertence. Do ponto de vista da LGPD, isso não é proibido, mas muda o nível de proteção que hoje vem "de graça" pela separação. Por isso, a minha orientação é a seguinte:

**1. Notas clínicas e CPF precisam de criptografia em nível de campo.**
Se os dados forem unificados, o conteúdo das notas clínicas (dado de saúde, portanto sensível) e o CPF dos pacientes devem ser gravados já criptografados pela aplicação, campo a campo. Ou seja: quem olhar diretamente a tabela vê apenas um texto embaralhado, e só a aplicação, com a chave, consegue ler.

**2. A criptografia em repouso do banco gerenciado não basta.**
Sei que o banco na AWS já está com criptografia de disco ativada. Isso é bom e deve continuar, mas protege contra outro tipo de risco (alguém levar o disco ou um backup). Não protege contra acesso pela aplicação nem contra consultas: qualquer pessoa ou rotina com acesso ao banco, ou uma consulta mal escrita que traga registros de outra clínica, enxerga os dados em texto claro. Com tudo numa base só, é exatamente esse cenário que precisamos reduzir.

**3. A busca por CPF precisa continuar funcionando.**
Sei que a recepção busca paciente por CPF o tempo todo. Como o CPF criptografado não pode ser pesquisado diretamente, sugiro guardar ao lado dele uma espécie de "impressão digital" do CPF: um valor calculado a partir do número com uma chave secreta (um hash com chave), que sempre dá o mesmo resultado para o mesmo CPF mas não permite voltar ao número. A busca compara essa impressão digital. A chave desse cálculo deve ficar separada do banco, como a chave de criptografia.

**4. Unificar só depois que isso estiver pronto.**
Minha recomendação é que a migração dos dados para a base única só aconteça depois que a criptografia dos campos estiver em produção e os dados existentes já tiverem sido convertidos. Não recomendo fazer as duas coisas ao mesmo tempo nem "criptografar depois".

Além disso, recomendo:

- registrar essa mudança no relatório de impacto (RIPD) que fizemos em 2021; posso atualizar o documento quando vocês tiverem a data;
- manter testes automatizados que garantam que uma clínica não enxerga dados de outra, já que a separação física deixa de existir;
- restringir quem tem acesso direto ao banco de produção e manter esse acesso registrado.

Fico à disposição para uma nova conversa se a equipe técnica tiver dúvidas sobre algum ponto.

Atenciosamente,

Paula Mendes
Encarregada de Dados (DPO), consultoria externa
Privacidade Consultoria

---

**De:** Rafael Lima <rafael.lima@horalis.example>
**Para:** Paula Mendes <paula.mendes@privacidade-consultoria.example>
**Cc:** Helena Duarte <helena.duarte@horalis.example>
**Data:** 2022-05-04 (quarta-feira), 09:15
**Assunto:** Re: Consulta sobre unificação da base de dados das clínicas

Oi Paula, bom dia.

Obrigado, ficou bem claro. Vamos fazer a criptografia de CPF e notas clínicas antes de qualquer unificação, com o hash para a busca por CPF do jeito que você descreveu. A Juliana já começou a olhar a parte de código esta semana.

Te aviso quando tivermos data para atualizar o RIPD.

Abraço,
Rafael

> ---------- Mensagem encaminhada ----------
> **De:** Rafael Lima <rafael.lima@horalis.example>
> **Para:** Juliana Prado <juliana.prado@horalis.example>, Marcos Teixeira <marcos.teixeira@horalis.example>, Thiago Fonseca <thiago.fonseca@horalis.example>, Beatriz Nogueira <beatriz.nogueira@horalis.example>
> **Data:** 2022-05-04 (quarta-feira), 09:21
> **Assunto:** Fwd: Re: Consulta sobre unificação da base de dados das clínicas
>
> Pessoal, segue a resposta da Paula. Resumindo: CPF e notas clínicas criptografados campo a campo + hash do CPF para busca, e só depois disso a gente mexe na estrutura do banco. Vou levar isso para a reunião do dia 20.
