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

## Deploy

`./scripts/deploy.sh` (precisa de acesso SSH à VPS).
