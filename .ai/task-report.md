# Task Report

## Goal

Add the async infrastructure foundation for Redis and RabbitMQ while keeping Laravel and PostgreSQL as the core application and source of truth.

## Files Changed

- `compose.yaml`
- `.env.example`
- `config/rabbitmq.php`
- `docs/dev/docker.md`
- `docs/architecture/async-infrastructure.md`
- `.ai/task-report.md`

## Implementation Summary

Completed the async infrastructure foundation already partially present in the repo. Redis remains configured for Laravel cache, rate limiting, and cache locks. RabbitMQ remains available locally with the management UI and now has a healthcheck plus complete local connection variables, including vhost and exchange type. Added a Laravel `config/rabbitmq.php` file for future RabbitMQ connection settings without adding a publisher. Expanded the architecture note with Redis, RabbitMQ, PostgreSQL, outbox, DLQ, retry, pub/sub, competing consumer, backpressure, and future notification delivery concepts.

## Architecture Decisions

Laravel remains the core application. PostgreSQL remains the authoritative store for business data. Redis is configured as temporary coordination infrastructure through Laravel's existing cache abstractions. RabbitMQ is present only as future integration-event infrastructure. The RabbitMQ config file reads environment values only; it does not declare exchanges, publish messages, install packages, or introduce consumers.

## Tradeoffs

`CACHE_STORE=redis` makes local Docker use Redis-backed cache/rate limit/lock behavior, while `QUEUE_CONNECTION=database` stays unchanged to avoid replacing Laravel's existing queue path before there is a specific need. RabbitMQ credentials remain the default local `guest` values for developer convenience; production credentials should be supplied by deployment secrets. Public intake rate limiting was documented as the best first Redis usage but not implemented, keeping this task to infrastructure and architecture foundation only.

## Tests

Not run, because this task's workflow does not allow Docker, Artisan, Composer, NPM, or test commands unless explicitly requested.

Suggested validation when command execution is approved:

```sh
docker compose config
```

## Risks

Existing local `.env` files will not automatically switch to Redis or include RabbitMQ values unless updated to match `.env.example`. RabbitMQ currently has no declared exchange or queues because there are no business events or consumers yet.

## Follow Ups

When the first real integration event is specified, add a small Laravel publishing boundary after PostgreSQL commits and define durable exchange, queue, routing key, retry, and dead-letter policies. A future learning note could be useful at `docs/learning/rabbitmq-integration-events.md`.
