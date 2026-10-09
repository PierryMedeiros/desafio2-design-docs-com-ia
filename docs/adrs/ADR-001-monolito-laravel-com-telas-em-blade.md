# ADR-001: Monólito Laravel com telas renderizadas em Blade

- **Status:** Accepted
- **Data:** 2019-04-02
- **Decisores:** Helena Duarte (CEO), Rafael Lima (CTO), Juliana Prado (desenvolvedora)
- **Relações:** nenhuma relação de substituição, emenda ou dependência. A [ADR-007](ADR-007-api-rest-versionada-na-url-no-monolito.md) depende desta.

## Contexto e problema

O MVP precisava estar em produção para três clínicas parceiras no começo de julho de 2019, três meses depois do kickoff. Helena chamou o prazo de "inegociável", porque a primeira mensalidade das parceiras começava a contar em julho. O escopo era de formulários e listagens: agenda por profissional, cadastro de pacientes, marcação e lembrete por e-mail. O time era de duas pessoas, Rafael e Juliana (ata do kickoff, `contexto/atas/2019-04-02-kickoff-tecnico.md`).

A pergunta era com que stack e em que formato construir o sistema.

## Opções consideradas

1. **Monólito PHP com Laravel e telas renderizadas no servidor em Blade**, sem front-end separado.
2. **Back-end em Node com uma SPA em React.**

## Decisão

Opção 1: um monólito em PHP com Laravel, com as telas renderizadas no servidor em Blade e sem front-end separado. Os motivos, registrados na ata:

- o time dominava PHP e Laravel e já tinha entregado projetos assim;
- com três meses de prazo, não havia espaço para aprender uma stack nova nem para manter dois projetos (API e front);
- as telas do MVP eram formulários e listagens, sem necessidade de interface muito dinâmica.

A opção 2 foi descartada porque ninguém do time tinha experiência real com ela e o prazo não comportava a curva de aprendizado. Rafael anotou que poderia fazer sentido rever "se houver app ou front mais rico".

No código, o projeto nasce em Laravel 5.8 (`c5c258b`, 2019-04-01). No mesmo dia, o commit `92b00f0` ("remove frontend do skeleton, vamos de blade puro") apaga `package.json`, `webpack.mix.js` e os componentes Vue do skeleton. A decisão vale até o HEAD: o Laravel passou por upgrades (6, 8, 9 e 10, este em `40d1dc9`), mas continua um monólito com painel em Blade (`resources/views/`). Quando veio o app, a API entrou no mesmo monólito ([ADR-007](ADR-007-api-rest-versionada-na-url-no-monolito.md)).

## Consequências

### Positivas

- O MVP entrou no ar para as três parceiras em 2019-07-01 (`contexto/slack/arquitetura.md`), dentro do prazo.
- Um projeto só e um deploy só, sem build de JavaScript: as telas são views Blade com um CSS simples (`public/css/painel.css`).
- A API do app, em 2021, reaproveitou models, regras de agenda e autenticação do mesmo código, em vez de exigir um serviço novo.

### Negativas

- O front fica limitado ao que dá para fazer com páginas renderizadas no servidor. Uma interface mais dinâmica pediria rever a decisão, como o próprio Rafael registrou.
- Painel, API, worker e scheduler compartilham o mesmo código e o mesmo ciclo de deploy. Uma migration ou um erro afeta todas as portas de entrada ao mesmo tempo, como no incidente de 2022-04-12 (`docs/postmortems/2022-04-12-deploy-travado.md`).

## Divergência entre fontes

O `docs/ARQUITETURA.md` (2019) lista como próximos passos "separar a agenda num microsserviço em 2020". Esse plano não aparece em nenhum commit, e o HEAD continua um monólito. Nenhuma fonte diz por que o plano foi abandonado. Adotei o código e o histórico como retrato do que foi decidido e tratei o microsserviço como intenção não realizada. **Needs Input:** quem decidiu manter a agenda no monólito, quando e por quê?

## Evidências

- Commits: `c5c258b` (projeto inicial em Laravel 5.8), `92b00f0` (remove o front-end do skeleton), `40d1dc9` (upgrade para Laravel 10 e PHP 8.2, ainda monólito).
- Arquivos: `composer.json`, `resources/views/layouts/painel.blade.php`, `routes/web.php`, `public/css/painel.css`.
- Rastros: `contexto/atas/2019-04-02-kickoff-tecnico.md` (seção 2), `docs/ARQUITETURA.md` (visão geral e próximos passos), `contexto/slack/arquitetura.md` (2019-07-01).
