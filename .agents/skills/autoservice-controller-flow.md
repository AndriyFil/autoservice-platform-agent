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
