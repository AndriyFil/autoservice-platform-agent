# Async Infrastructure

AutoService uses async infrastructure to support operational reliability without changing the core Laravel application shape. Laravel remains the main application, and PostgreSQL remains the source of truth for business data.

## Redis Role

Redis is used for fast, temporary coordination data:

- application cache through Laravel's `CACHE_STORE=redis`;
- rate limiting through Laravel's rate limiter, which stores throttle counters in the configured cache store;
- cache locks through Laravel's cache lock API, using the Redis cache store and lock connection.

Redis should hold data that can expire or be rebuilt. It should not hold authoritative workshop, customer, repair order, booking request, estimate, or payment data.

## RabbitMQ Role

RabbitMQ is the integration event broker for future asynchronous communication between AutoService and external consumers. The first planned consumer is a future Go Telegram bot, but that service is not part of this slice.

RabbitMQ is available in Docker now so infrastructure, local access, and vocabulary are stable before business events are introduced. Laravel does not publish real business events yet.

## Messaging Terms

Producer: the application or service that publishes a message. In the future, Laravel may become a producer after a business transaction commits and an integration event is ready to send.

Consumer: the application or service that reads messages from a queue. A future Go Telegram bot can be a consumer when Telegram notification requirements are specified.

Exchange: the RabbitMQ routing point that receives published messages. This project reserves `autoservice.events` as the future integration event exchange.

Queue: the durable buffer that stores messages for a consumer. Each consumer type should have its own queue so slow or unavailable consumers do not block unrelated consumers.

Routing key: the string attached to a published message so RabbitMQ can route it from an exchange to matching queues. Future keys should describe integration facts, such as `booking_request.created`, not commands such as `send_telegram_now`.

Ack: a consumer acknowledgement that tells RabbitMQ a message was processed successfully and can be removed from the queue.

Nack: a consumer negative acknowledgement that tells RabbitMQ processing failed. Depending on queue policy, the message may be retried, requeued, or routed to a dead-letter queue.

Dead-letter queue: a queue for messages that cannot be processed after the configured retry or rejection path. Dead-letter queues preserve failed messages for inspection and recovery without losing data or blocking the main queue.

## Source Of Truth

PostgreSQL remains the source of truth because it stores the durable business state: workshops, memberships, customers, vehicles, booking requests, repair orders, estimates, documents, and audit-relevant records. Business workflows should commit their state to PostgreSQL first.

RabbitMQ is not a database. It is a delivery mechanism for messages between systems. Messages can be delayed, retried, duplicated, or dead-lettered, so consumers must treat them as integration notifications and read authoritative state from PostgreSQL or an explicit API when they need current business facts.

Redis is also not a database for business state. It is intentionally used for fast temporary cache, counters, and locks.

## Current Boundary

This foundation adds Docker services and environment variables only. It does not:

- publish business events;
- add RabbitMQ PHP packages;
- add a Go service;
- add a Telegram bot;
- replace Laravel queues;
- replace PostgreSQL persistence;
- move AutoService toward microservices.
