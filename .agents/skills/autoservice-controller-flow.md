# AutoService Controller Flow

## Principle

Controllers are orchestration only.

## Controller may

- Receive a FormRequest.
- Pass validated data or route models into an Action or Query/read class.
- Write session only when it is an HTTP/session concern.
- Return a View, Redirect, JSON Resource, Inertia Response, or Response.

## Controller must not

- Contain business rules.
- Decide status transitions.
- Build complex queries.
- Calculate domain values.
- Coordinate many model changes directly.
- Open business transactions.
- Call `DB::transaction`.

## Preferred write flow

```txt
Controller -> FormRequest -> Action -> Model/DB
```

## Preferred read/list flow

```txt
Controller -> optional FormRequest -> Query/read class -> Inertia props/View/Resource
```

## Exception for simple reads

For very small read-only pages or trivial CRUD reads, direct model access in a controller is acceptable only if:

- there is no business workflow
- there is no status transition
- there is no complex filtering
- there is no coordination of multiple models
- there is no duplication risk
- the controller remains small and obvious

If query/mapping grows, extract a feature-specific Query/read class.

## FormRequest

Use a FormRequest when:

- validation is non-trivial
- authorization belongs to the request
- input needs preparation before validation

FormRequest must not:

- persist data
- execute workflows
- call Actions
- make business transitions

## Action

Use an Action for one business use case.

Action may:

- coordinate model writes
- own `DB::transaction`
- enforce business invariants
- return the created/updated model or result

Action must not:

- render responses
- redirect
- write flash/session data
- become a broad service object

## Policy

Use policies only when authorization decisions exceed simple auth/route guard or active workshop membership checks.

Policy answers:

```txt
Can the user do this?
```

Policy does not execute the business operation.

## Active workshop

- Do not use direct `user.workshop_id`.
- Resolve workshop access through `WorkshopUser`.
- Scope queries and authorization by active workshop membership.
