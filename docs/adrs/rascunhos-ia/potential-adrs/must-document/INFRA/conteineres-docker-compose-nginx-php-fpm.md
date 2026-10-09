# ADR Potencial: Empacotamento em contêineres Docker (nginx + PHP-FPM, Compose, entrypoint com migrate/seed, imagens com tag fixa)

**Módulo**: INFRA
**Categoria**: Infraestrutura / Plataforma
**Prioridade**: Must Document (Score: 110)
**Data de Identificação**: 2026-10-09

---

## O que foi identificado

O Horalis é distribuído como imagem Docker única (`docker/Dockerfile`: PHP-FPM sobre Debian bookworm, extensões `pdo_pgsql pgsql zip pcntl bcmath`, composer em multi-stage) usada por três serviços do Compose (`app`, `worker`, `scheduler`) mais `nginx`, `postgres`, `redis` e `s3`. O Compose existe desde o commit inicial (`c5c258b`), mas mudou de natureza:
- **2019 a 2023:** código montado por volume (`.:/var/www/html`) para desenvolvimento, nginx 1.15, `mailhog` para e-mail de desenvolvimento, portas do Postgres expostas.
- **2023-07-11 (`40d1dc9`):** reescrito com âncora YAML `x-app` (`horalis-app:dev`, `.env` montado), sem montar o código-fonte, nginx 1.25, `composer.lock` passou a ser versionado (antes ignorado). Slack 2023-07-05: "o composer.lock vai passar a ser versionado".
- **2024-06-11 (`9ca4b39`):** `mailhog` removido; `MAIL_MAILER=log` (consequência do fim do canal de e-mail).
- **2025-03-11 (`eff7ee9`):** entrypoint espera o Postgres e roda `migrate --force` e `db:seed --force` automaticamente; healthchecks em postgres/redis/app e `depends_on: service_healthy`.
- **2025-10-14 (`5bedfc9`)**: banco `horalis_test` criado por script em `docker/postgres`.
- **2025-11-18 (`989d7cc`)**: imagens com tag fixa (`nginx:1.27.5-alpine`, `postgres:16.15-alpine`, `redis:7.2.16-alpine`, `php:8.2.34-fpm-bookworm`, `composer:2.8.12`).

A versão de PHP/Laravel que a imagem carrega evoluiu em upgrades próprios: `5f57d55` (PHP 7.4/Laravel 6, 2019-12-10), `2069974` (PHP 8.0/Laravel 8, 2021-01-12), `9cae2ef` (PHP 8.1/Laravel 9, 2022-09-13) e `40d1dc9` (PHP 8.2/Laravel 10, 2023-07-11). Ponto de atenção: a produção usa a AWS e a pipeline não está no repositório; não é possível confirmar se a imagem do repositório é a mesma usada em produção (ver ADR de hospedagem).

Atenção: o entrypoint executa `db:seed --force` em toda subida do `php-fpm`. Se a mesma imagem fosse usada em produção, o seed rodaria a cada deploy; isso está implícito e não documentado.

## Por que isso pode merecer um ADR

- **Impacto**: todos os desenvolvedores e o CI; define como o app sobe.
- **Trade-offs**: reprodutibilidade vs peso; seeds automáticos facilitam o desenvolvimento mas são arriscados fora dele.
- **Complexidade**: gestão de versões fixas e de contêiner único para três papéis.
- **Conhecimento do time**: todos usam `docker compose up`.
- **Temporal**: estável com refatorações em 2023 e 2025.

## Evidências encontradas no código

### Arquivos principais
- [`docker-compose.yml`](../../../../../docker-compose.yml) - `x-app`, serviços e healthchecks
- [`docker/Dockerfile`](../../../../../docker/Dockerfile), [`docker/entrypoint.sh`](../../../../../docker/entrypoint.sh), [`docker/aguardar-banco.php`](../../../../../docker/aguardar-banco.php)
- [`docker/nginx/default.conf`](../../../../../docker/nginx/default.conf) (`client_max_body_size 20M`, gzip desde `239b90b`, 2024-11-12), [`docker/php/horalis.ini`](../../../../../docker/php/horalis.ini)

### Evidência de código
```
# docker/entrypoint.sh
php artisan migrate --force
php artisan db:seed --force
touch /tmp/horalis-pronto
```

### Análise de impacto
- Introduzido: 2019-04-01 (`c5c258b`); redesenho em `40d1dc9` (2023-07) e `eff7ee9` (2025-03)
- Temas: reprodutibilidade, upgrade de runtime, versões fixas

### Alternativas
- Nenhuma registrada (sem Kubernetes, Sail ou Vagrant mencionados).

## Questões a responder no ADR

- A imagem é a mesma em produção? Como a imagem é construída e publicada?
- O seed no entrypoint é só para desenvolvimento?
- Política de atualização das versões fixas.

## ADRs potenciais relacionados
- `worker-e-scheduler-em-processos-dedicados.md`
- `aws-como-hospedagem-e-pipeline-de-deploy.md`
- `DATA/postgresql-como-banco-relacional.md`
