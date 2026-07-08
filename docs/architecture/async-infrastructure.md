# Async Infrastructure

AutoService uses async infrastructure to improve reliability without changing the core application shape. Laravel remains the main monolith. PostgreSQL remains the source of truth for business data. Redis and RabbitMQ support technical coordination and future integration messaging.

This foundation does not add a Go service, Telegram bot, notification sending, RabbitMQ consumers, RabbitMQ business publishers, an outbox table, or notification delivery tables.

## Redis Role

Redis is for fast temporary technical state:

- cache values that can be rebuilt, such as dashboard counts;
- rate limiting counters, such as public intake submissions by workshop and IP address;
- distributed locks, such as preventing two workers from generating the same estimate PDF at the same time;
- optional sessions later if the project chooses to move sessions away from PostgreSQL.

Redis is not the source of truth. It must not store authoritative workshops, customers, vehicles, booking requests, repair orders, estimates, payments, or notification history.

In local Docker, Laravel uses Redis through `CACHE_STORE=redis`. Laravel's rate limiter and cache lock APIs can use that cache store without making Redis the business database.

## RabbitMQ Role

RabbitMQ is the future message broker for integration events. It moves messages between producers and consumers without forcing Laravel to wait for external systems during a web request.

Producer means the application that publishes a message. In AutoService, Laravel may later publish integration events after a business transaction commits.

Consumer means an application or process that reads messages from a queue. Future examples could include Telegram notifications, email notifications, or analytics consumers.

Exchange means the RabbitMQ routing entry point. Producers publish messages to an exchange, not directly to every consumer queue.

Queue means a durable buffer of messages waiting for a consumer. A slow Telegram consumer should not block a separate analytics queue.

Routing key means the event name attached to a message. Future keys should describe facts that happened, such as `booking_request.created`, not commands such as `send_telegram_now`.

Binding means a rule that connects an exchange to a queue. For example, a queue can bind to `booking_request.created` to receive only that event, or to `booking_request.*` to receive several booking request events.

Ack means a consumer acknowledgement. The consumer should ACK only after successful handling, so RabbitMQ can remove the message from the queue.

Nack means a negative acknowledgement. It tells RabbitMQ the consumer failed to handle the message. The message may be retried, dead-lettered, or requeued depending on queue policy.

Retry queue means a queue or delayed strategy used for temporary failures, such as Telegram being unavailable for a short time. Retries must not become hot infinite requeue loops that immediately fail and re-enter the same queue forever.

Dead-letter queue, or DLQ, means a queue for messages that could not be processed after the retry or rejection path. Invalid messages and permanently failing messages should go to a DLQ so they do not block normal processing.

Competing consumers means several workers reading from one queue. Each message goes to one of those workers, which lets the same workload scale horizontally.

Pub/sub means several queues are bound to one exchange and each queue receives its own copy of the matching message. For example, Telegram, email, and analytics queues can all receive `booking_request.created`.

Backpressure means consumers are slower than producers and queue depth grows. RabbitMQ makes that pressure visible, but the fix is operational: slow publishing, add consumers, improve handlers, or degrade non-critical work.

If a consumer dies before ACK, RabbitMQ returns the unacked message to the queue so another consumer can process it. Consumers must therefore be safe to run the same message more than once.

## PostgreSQL Role

PostgreSQL stores durable business state:

- workshops and workshop memberships;
- customers and vehicles;
- booking requests;
- repair orders;
- estimates and documents;
- future outbox events;
- future notification delivery logs.

Business workflows should commit their state to PostgreSQL first. Redis can expire or be rebuilt. RabbitMQ can redeliver messages. PostgreSQL is where AutoService keeps the authoritative answer.

## Future Event Examples

Possible future integration event routing keys:

- `booking_request.created`
- `repair_order.status_changed`
- `estimate.generated`
- `estimate.approval_required`

These are facts that happened in AutoService. They do not promise that a Telegram message, email, or analytics record has already been delivered.

## Future RabbitMQ Topology

Reserved exchange:

```text
autoservice.events
```

Exchange type:

```text
topic
```

Possible queues:

- `telegram.notifications`
- `email.notifications`
- `analytics.events`

Example bindings:

- `telegram.notifications` listens to `booking_request.created`
- `email.notifications` listens to `booking_request.created`
- `analytics.events` listens to `booking_request.*`

Several consumers on one queue compete for messages. Several queues bound to one exchange each receive their own copy of matching messages.

## Future Outbox Pattern

RabbitMQ publishing should not happen directly from controllers, and it should not be added to the request flow without an outbox plan.

The future outbox pattern should work like this:

1. Laravel writes the business row and an integration event row in one PostgreSQL transaction.
2. A publisher process later reads unpublished outbox rows.
3. The publisher sends those events to RabbitMQ.
4. The publisher marks each outbox row as published only after RabbitMQ confirms the publish.

This protects against the case where the PostgreSQL commit succeeds but RabbitMQ publishing fails. Without an outbox, AutoService could save a booking request and silently lose the integration event that should notify another system.

Outbox is not DLQ. The outbox is for events that are not yet published to RabbitMQ. A DLQ is for messages that were already published but consumers failed to process.

## Future Notification Deliveries

Future Telegram and email consumers should store delivery state in PostgreSQL, likely in a table such as `notification_deliveries`.

That table would help track:

- delivery attempts;
- status;
- last error;
- provider message id, such as a Telegram message id.

Possible statuses:

- `pending`
- `processing`
- `retry_scheduled`
- `sent`
- `failed`

Delivery logs prevent most duplicate side effects because a consumer can check whether a notification has already been sent before calling an external provider again.

They do not guarantee absolute exactly-once delivery with external APIs. Telegram does not participate in the same PostgreSQL transaction as AutoService, so a crash can still happen after Telegram accepts a message but before AutoService records `sent`. Consumers must be idempotent where possible and safe to retry.

## First Redis Use Cases

The first Redis-backed product use cases should stay small and observable:

- public intake rate limiting by workshop and IP address;
- estimate PDF generation distributed lock per repair order or estimate version;
- dashboard counts cache for expensive repeated totals.

Public intake rate limiting is the most product-relevant first implementation candidate because it protects customer-facing endpoints without changing the intake domain model. It should use Laravel's rate limiter and return HTTP 429 for excessive submissions. It was not implemented in this infrastructure slice because the requested foundation can be completed with configuration and documentation only.

## Current Boundary

This slice provides local infrastructure and configuration:

- Redis service in Docker;
- RabbitMQ service with management UI in Docker;
- Redis environment defaults for cache, rate limiting, and locks;
- RabbitMQ environment defaults and Laravel config values;
- documentation for the intended async architecture.

This slice does not:

- use Redis as the main integration event broker;
- replace PostgreSQL as source of truth;
- replace Laravel's current `QUEUE_CONNECTION=database`;
- publish RabbitMQ business events;
- declare runtime exchanges or queues;
- add RabbitMQ PHP packages;
- add a Go service;
- add Telegram or email notifications.
