# Task Report

## Goal

Add the async infrastructure foundation for Redis and RabbitMQ while keeping Laravel and PostgreSQL as the core application and source of truth.

## Files Changed

- `compose.yaml`
- `.env.example`
- `docs/dev/docker.md`
- `docs/architecture/async-infrastructure.md`
- `.ai/task-report.md`

## Implementation Summary

Added Redis to Docker for Laravel cache, rate limiting, and cache locks. Added RabbitMQ with the management UI for future integration events. Updated environment examples with Redis cache settings and RabbitMQ connection variables. Documented the project-specific roles and boundaries for Redis, RabbitMQ, producers, consumers, exchanges, queues, routing keys, ack/nack, and dead-letter queues.

## Architecture Decisions

Laravel remains the core application. PostgreSQL remains the authoritative store for business data. Redis is configured as temporary coordination infrastructure through Laravel's existing cache abstractions. RabbitMQ is present only as future integration-event infrastructure; no business event publishing, Go service, Telegram bot, or RabbitMQ application package was added.

## Tradeoffs

`CACHE_STORE=redis` makes local Docker use Redis-backed cache/rate limit/lock behavior, while `QUEUE_CONNECTION=database` stays unchanged to avoid replacing Laravel's existing queue path before there is a specific need. RabbitMQ credentials remain the default local `guest` values for developer convenience; production credentials should be supplied by deployment secrets.

## Tests

Passed:

```sh
docker compose config
```

## Risks

Existing local `.env` files will not automatically switch to Redis unless updated to match `.env.example`. RabbitMQ currently has no declared exchange or queues because there are no business events or consumers yet.

## Follow Ups

When the first real integration event is specified, add a small Laravel publishing boundary after PostgreSQL commits and define durable exchange, queue, routing key, retry, and dead-letter policies. A future learning note could be useful at `docs/learning/rabbitmq-integration-events.md`.
