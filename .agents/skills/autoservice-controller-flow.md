# AutoService Controller Flow

## Principle

Controllers are orchestration only.

## Controller may

- Receive a FormRequest.
- Pass validated data or route models into an Action or Query.
- Return a View, Redirect, JSON Resource, or Response.

## Controller must not

- Contain business rules.
- Decide status transitions.
- Build complex queries.
- Calculate domain values.
- Coordinate many model changes directly.

## Preferred write flow

Controller → FormRequest → Action → Model

## Preferred read/list flow

Controller → FormRequest → Query → View/Resource

## Exception

For very small read-only pages or trivial CRUD operations, direct model access in a controller is acceptable only if:

- there is no business workflow
- there is no status transition
- there is no complex filtering
- there is no coordination of multiple models
- there is no duplication risk
- the controller remains tiny

## Required AutoService Flow

Use this default flow for non-trivial features:

Controller -> FormRequest -> Action -> Model/DB

Do not produce "quick MVP now, clean later" code.
Use simple but correct architecture from the beginning.

Controller:
- HTTP orchestration only
- call FormRequest
- call Action
- write session only when it is an HTTP/session concern
- redirect or render response
- no business transactions
- no `DB::transaction`
- no direct multi-model business workflows

FormRequest:
- validation
- request-level authorization when appropriate
- no persistence
- no business workflows

Action:
- one business use case
- transactional operations
- coordinates model writes
- may use `DB::transaction` when one business operation writes multiple records
- returns result to controller

Model:
- relationships
- casts
- fillable or guarded
- local scopes if useful
- no large business workflows

Policy:
- authorization decisions when needed
- do not create a policy unless the feature needs authorization beyond simple route/auth guard

Service guidance:
- avoid broad services
- do not create god services that collect unrelated use cases
- prefer Actions for use cases

Active workshop:
- do not use direct `user.workshop_id`
- resolve workshop access through `WorkshopUser`
- scope queries and authorization by active workshop membership
