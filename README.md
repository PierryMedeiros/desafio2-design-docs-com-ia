# Horalis

Agendamento para clínicas: painel da recepção, API do app do paciente e lembretes de consulta.

## Ambiente local

Precisa de Docker com o Compose.

```
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

- painel: http://localhost:8080
- health check: http://localhost:8080/health

O código vai para dentro da imagem no build. Depois de mudar código, rode `docker compose up -d --build` de novo.

Usuários do seed (senha `password`): admin@bem-estar.test, recepcao@bem-estar.test e recepcao@fisio-movimento.test. Paciente para a API: paciente@bem-estar.test.

## Testes

```
docker compose exec postgres createdb -U horalis horalis_testing
docker compose exec app php artisan test
```

## Lembretes

```
docker compose exec app php artisan lembretes:enfileirar
```

O worker processa a fila `notificacoes`. Em desenvolvimento os canais usam o driver `log`, então o envio aparece em `docker compose logs worker`.

## Estilo de código

```
docker compose exec app ./vendor/bin/pint
```
