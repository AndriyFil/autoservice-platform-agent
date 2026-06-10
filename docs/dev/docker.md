# Docker Development

## Services

- `app`: Laravel development server on container port `8000`
- `node`: Vite development server on container port `5173`
- `postgres`: PostgreSQL database on container port `5432`

## Ports

- Laravel: `http://localhost:8080`
- Vite: `http://localhost:5173`
- PostgreSQL: `localhost:5432`

## Environment

Laravel connects to PostgreSQL with:

- `DB_CONNECTION=pgsql`
- `DB_HOST=postgres`
- `DB_PORT=5432`
- `DB_DATABASE=autoservice`
- `DB_USERNAME=autoservice`
- `DB_PASSWORD=autoservice`

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
http://localhost:8080
```
