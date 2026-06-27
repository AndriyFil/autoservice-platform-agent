# AutoService Product Vision v2

## Vision

AutoService is a modern platform for auto service intake and workshop management.

The platform follows a chat-first user experience approach.

Customers should be able to describe their problem naturally instead of filling long and complex forms. The system progressively collects the required information and converts it into structured workshop data.

The goal is to reduce friction, increase booking completion rates, and make communication with workshops feel simple and natural.

---

## Core Principles

### Conversation First

Customer interactions should start with a simple question:

"What happened to your car?"

The system should collect information progressively rather than presenting large forms.

### One Step At A Time

Users should answer one question at a time.

Avoid overwhelming customers with many fields, sections, or configuration options on a single screen.

### Progressive Data Collection

Required information should be collected only when needed.

Examples:

- describe the problem
- preferred date
- vehicle information
- contact information

### Internal Complexity Stays Internal

Customers should never be exposed to internal workshop terminology.

Terms such as:

- BookingRequest
- RepairOrder
- WorkshopUser

are internal implementation details.

Customers only see concepts they understand:

- Request
- Appointment
- Service
- Vehicle

### Consistent Experience

The same customer journey should work across all future customer-facing channels.

Business rules should remain independent from the presentation layer.

---

## Product Positioning

AutoService is not a traditional CRM.

AutoService is a customer-first service intake and workshop management platform.

Workshop management remains important, but customer interaction and simplicity are first-class product goals.

---

## Core Domain

Workshop

WorkshopUser

Customer

Vehicle

BookingRequest
- customer intake
- service request
- lead

RepairOrder
- actual workshop work
- operational document

---

## MVP Goal

A workshop should be able to:

- receive requests
- manage customers
- manage vehicles
- create repair orders
- track work status

A customer should be able to:

- describe a problem
- submit a request with minimal effort
- receive a clear and simple experience

---

## Product Rule

When a choice exists between:

1. a simpler customer experience
2. exposing internal business complexity

the simpler customer experience should be preferred.
