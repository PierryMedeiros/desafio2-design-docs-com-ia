# Arquitetura do Horalis

Última atualização: julho de 2019 (Rafael)

Este documento descreve como o Horalis está organizado. A ideia é que qualquer pessoa nova no time consiga entender o sistema lendo só isto antes de abrir o código.

## Visão geral

O Horalis é uma aplicação web para clínicas pequenas e médias marcarem consultas. A recepção e os profissionais usam o painel pelo navegador. Os pacientes não acessam o sistema: só recebem os lembretes por e-mail.

É um monólito Laravel (5.8) com as telas renderizadas no servidor em Blade. Não existe front-end separado nem build de JavaScript: cada tela é uma view Blade e o visual vem de um CSS simples em `public/css/painel.css`.

```
navegador --> nginx --> PHP-FPM (Laravel) --> PostgreSQL
                              |
                              +--> disco local (storage/app/anexos)

cron --> php artisan schedule:run --> lembretes:enviar --> SMTP
```

## Banco de dados e clínicas

O banco é PostgreSQL. Cada clínica tem o seu próprio schema dentro do mesmo banco:

- o schema `public` guarda só o que é comum a todas: a tabela `tenants` (as clínicas, com o nome do schema de cada uma) e a `users` (login da equipe);
- cada clínica tem um schema `clinica_<slug>` com as tabelas do dia a dia: profissionais, serviços, disponibilidades, bloqueios, pacientes, agendamentos e anexos.

O isolamento por schema existe por causa da LGPD. Os dados de saúde de uma clínica nunca ficam na mesma tabela que os de outra, então um filtro esquecido numa consulta não tem como mostrar o paciente de outra clínica. Essa foi a orientação do advogado da empresa.

Na prática:

- o middleware `DefinirSchemaTenant` pega a clínica do usuário logado e troca o `search_path` da conexão para `clinica_<slug>, public`;
- as migrations das tabelas da clínica ficam em `database/migrations/tenant` e rodam em todos os schemas com `php artisan tenants:migrate`;
- as migrations do `public` continuam em `database/migrations` e rodam com o `php artisan migrate` de sempre;
- clínica nova: `php artisan tenants:criar "Nome da Clínica" admin@clinica.example` cria o registro, o schema e o usuário admin.

Toda migration nova de tabela da clínica vai para `database/migrations/tenant`. Se for para a pasta normal, a tabela é criada só no `public` e a tela quebra.

## Agenda

- `disponibilidades`: faixas de horário de cada profissional por dia da semana;
- `bloqueios`: dias em que o profissional, ou a clínica inteira, não atende;
- `agendamentos`: paciente, profissional, serviço, início, fim e status (agendado, confirmado ou cancelado).

O fim do agendamento é calculado pela duração do serviço. Não deixamos dois agendamentos do mesmo profissional se sobreporem.

## Lembretes

O paciente recebe um e-mail de lembrete com até 24 horas de antecedência. Quem envia é o comando `php artisan lembretes:enviar`, agendado no `app/Console/Kernel.php` para rodar a cada 10 minutos; o cron do servidor chama o `schedule:run` a cada minuto.

O comando passa por todas as clínicas, busca os agendamentos das próximas 24 horas que ainda não tiveram lembrete, envia o e-mail na hora (de forma síncrona, pelo SMTP configurado no `.env`) e grava `lembrete_enviado_em`.

## Anexos

Exames e documentos enviados pela recepção ficam no disco local do servidor, em `storage/app/anexos` (disco `anexos` no `config/filesystems.php`), separados por agendamento. O download passa pelo Laravel (`GET /anexos/{id}`), que confere o login antes de entregar o arquivo.

## Autenticação

Login por e-mail e senha, só para a equipe da clínica. A sessão é a padrão do Laravel, em arquivo (`storage/framework/sessions`). Papéis: admin, recepção e profissional.

## Infraestrutura

Produção roda numa única VPS: nginx, PHP-FPM, PostgreSQL e o cron no mesmo servidor, com backup diário do banco feito pelo provedor.

No desenvolvimento usamos o `docker-compose.yml` da raiz (app, nginx, postgres e mailhog, que mostra os e-mails em http://localhost:8025). O deploy é feito com `scripts/deploy.sh`.

## Próximos passos

- App do paciente em React Native, consumindo uma API GraphQL.
- Separar a agenda num microsserviço em 2020, quando o número de clínicas justificar.
