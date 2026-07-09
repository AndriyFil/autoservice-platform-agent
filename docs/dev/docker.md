# Docker Development

## Services

- `app`: Laravel development server on container port `8000`
- `node`: Vite development server on container port `5173`
- `postgres`: PostgreSQL database on container port `5432`
- `redis`: Redis cache, rate limiter, and lock backend on container port `6379`
- `rabbitmq`: RabbitMQ broker with management UI on container ports `5672` and `15672`

## Ports

- Laravel fallback: `http://localhost:8080`
- Public surface: `http://autoservice.test:8080`
- Admin surface: `http://admin.autoservice.test:8080`
- Vite: `http://localhost:5173`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`
- RabbitMQ AMQP: `localhost:5672`
- RabbitMQ management UI: `http://localhost:15672`

## Local Hostnames

Add these hostnames on the host machine:

```txt
127.0.0.1 autoservice.test
127.0.0.1 admin.autoservice.test
```

## Environment

Laravel connects to PostgreSQL with:

- `DB_CONNECTION=pgsql`
- `DB_HOST=postgres`
- `DB_PORT=5432`
- `DB_DATABASE=autoservice`
- `DB_USERNAME=autoservice`
- `DB_PASSWORD=autoservice`
- `APP_URL=http://localhost:8080`
- `PUBLIC_APP_URL=http://autoservice.test:8080`
- `ADMIN_APP_URL=http://admin.autoservice.test:8080`
- `CACHE_STORE=redis`
- `REDIS_HOST=redis`
- `REDIS_PORT=6379`
- `RABBITMQ_HOST=rabbitmq`
- `RABBITMQ_PORT=5672`
- `RABBITMQ_USER=guest`
- `RABBITMQ_PASSWORD=guest`
- `RABBITMQ_VHOST=/`
- `RABBITMQ_EXCHANGE=autoservice.events`
- `RABBITMQ_EXCHANGE_TYPE=topic`

Redis is the Laravel cache backend for local Docker. Laravel's cache, rate limiter, and cache lock APIs use the configured cache store.

RabbitMQ is available for future integration events. The application does not publish business events to RabbitMQ yet.

## Setup

Run:

```sh
docker compose up -d --build
docker compose exec app composer install
docker compose exec node npm ci
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh
```

Open:

```text
http://autoservice.test:8080
http://admin.autoservice.test:8080
```
