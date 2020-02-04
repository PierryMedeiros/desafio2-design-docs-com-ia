# Horalis

Agendamento para clínicas.

## Ambiente local

Precisa de Docker e docker-compose.

```
cp .env.example .env
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

O painel fica em http://localhost:8080 e os e-mails enviados aparecem no mailhog, em http://localhost:8025.

Usuários do seed (senha `password`): admin@bem-estar.test, recepcao@bem-estar.test e recepcao@fisio-movimento.test.

## Clínicas

Cada clínica tem um schema próprio no Postgres. Depois de criar uma migration em `database/migrations/tenant`, rode:

```
docker-compose exec app php artisan tenants:migrate
```

Mais detalhes em `docs/ARQUITETURA.md`.

## Testes

Os testes usam o banco `horalis_testing`, que precisa ser criado uma vez:

```
docker-compose exec postgres createdb -U horalis horalis_testing
docker-compose exec app vendor/bin/phpunit
```

## Lembretes

Para mandar os lembretes na hora, sem esperar o cron:

```
docker-compose exec app php artisan lembretes:enviar
```

## Estilo de código

```
docker-compose exec app vendor/bin/php-cs-fixer fix
```

## Deploy

`./scripts/deploy.sh` (precisa de acesso SSH à VPS).
