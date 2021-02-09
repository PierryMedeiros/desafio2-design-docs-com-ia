# Horalis

Agendamento para clínicas: painel da recepção e lembretes de consulta.

## Ambiente local

Precisa de Docker e docker-compose.

```
cp .env.example .env
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tenants:migrate
docker-compose exec app php artisan db:seed
```

- painel: http://localhost:8080
- e-mails (mailhog): http://localhost:8025

Usuários do seed (senha `password`): admin@bem-estar.test, recepcao@bem-estar.test e recepcao@fisio-movimento.test.

O contêiner `worker` processa a fila `notificacoes` (lembretes). Depois de mexer em job, reinicie: `docker-compose restart worker`.

## Clínicas

Cada clínica tem um schema próprio no Postgres. Depois de criar uma migration em `database/migrations/tenant`, rode `php artisan tenants:migrate`. Mais detalhes em `docs/ARQUITETURA.md`.

## Testes

Os testes usam o banco `horalis_testing`, que precisa ser criado uma vez:

```
docker-compose exec postgres createdb -U horalis horalis_testing
docker-compose exec app php artisan test
```

## Lembretes

Para enfileirar os lembretes na hora, sem esperar o cron:

```
docker-compose exec app php artisan lembretes:enfileirar
```

Em desenvolvimento o canal de SMS usa o driver `log` (`LEMBRETES_SMS_DRIVER=log`), então o envio aparece no log do worker.

## Estilo de código

```
docker-compose exec app vendor/bin/php-cs-fixer fix
```

## Deploy

`./scripts/deploy.sh` (precisa de acesso SSH à VPS).
