# Horalis

Agendamento para clínicas: painel da recepção e dos profissionais, API do app do paciente e lembretes de consulta.

## Rodando localmente

Pré-requisito: Docker com o Compose.

```
cp .env.example .env
docker compose up -d --build
```

Na primeira subida o contêiner `app` roda as migrations e o seed. Depois disso:

- painel: http://localhost:8080
- health check: http://localhost:8080/health

### Serviços

| Serviço | O que faz |
|---|---|
| nginx | servidor web, porta 8080 |
| app | PHP-FPM com a aplicação |
| worker | processa as filas `notificacoes` e `default` |
| scheduler | roda o `schedule:work` (lembretes a cada 10 minutos) |
| postgres | bancos `horalis` e `horalis_test` |
| redis | filas, cache e sessão |
| s3 | armazenamento de objetos local (bucket `horalis-anexos`), porta 9090 |

### Usuários do seed

Senha de todos: `password`.

| E-mail | Clínica | Uso |
|---|---|---|
| admin@bem-estar.test | Clínica Bem Estar | painel (admin) |
| recepcao@bem-estar.test | Clínica Bem Estar | painel (recepção) |
| recepcao@fisio-movimento.test | Fisio Movimento | painel (recepção) |
| paciente@bem-estar.test | Clínica Bem Estar | API do app |

## Testes

```
docker compose exec app php artisan test
```

Os testes usam o banco `horalis_test`.

## Lembretes

O scheduler roda `lembretes:enfileirar` a cada 10 minutos. Os jobs vão para a fila `notificacoes` e o worker envia por WhatsApp e, se não der, por SMS. Em desenvolvimento os dois canais usam o driver `log`, então o envio aparece em `docker compose logs worker`.

Para disparar na hora:

```
docker compose exec app php artisan lembretes:enfileirar
```

## API do app (v1)

```
POST   /api/v1/auth/token          {email, senha, clinica}
GET    /api/v1/horarios?profissional_id=&servico_id=&data=AAAA-MM-DD
GET    /api/v1/agendamentos
POST   /api/v1/agendamentos        {profissional_id, servico_id, inicio: "AAAA-MM-DD HH:MM", convenio?, reagendar_de?}
DELETE /api/v1/agendamentos/{id}
```

As rotas, exceto a do token, pedem `Authorization: Bearer <token>`.

## Comandos úteis

- `php artisan relatorios:faltas bem-estar --mes=2025-08`
- `php artisan pacientes:criptografar`
- `./vendor/bin/pint` para formatar o código
