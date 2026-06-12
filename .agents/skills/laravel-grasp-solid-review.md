# Laravel GRASP + SOLID Review

## Philosophy

Use GRASP and SOLID as review tools, not as dogma.

Do not punish simple Laravel code if it is:
- readable
- testable
- consistent with the project
- not duplicating business rules
- not hiding logic in the wrong layer

## Review order

1. Broken business rules
2. Invalid status transitions
3. Missing authorization
4. Missing validation
5. Cross-workshop data leaks
6. Fat controllers
7. God services
8. God components
9. Duplicated logic
10. Complex queries in wrong layer
11. N+1 queries
12. Over-engineering

## Red flags

Backend:
- Controller contains business decisions.
- FormRequest contains business workflow.
- Service has many unrelated public methods.
- Repository only wraps simple Eloquent calls.
- DTO exists only to move three fields once.
- Interface has only one implementation without a real reason.
- Pattern is added before there is pain.

Frontend:
- Page component contains large table/list/modal logic.
- Store is used for page-local Inertia props.
- Types are duplicated inline across components.
- Feature component is moved to shared UI before reuse exists.

## Output format

When reviewing, return:

1. Critical issues
2. Architectural issues
3. Simpler alternative
4. Suggested file-level changes
5. Tests to add or update

## AutoService Architecture Checklist

Review against the accepted backend flow:

```txt
Controller -> FormRequest -> Action -> Model/DB
```

Controller thinness:
- controller performs HTTP orchestration only
- controller calls FormRequest and Action for writes
- controller may write session only for HTTP/session concerns
- controller redirects or renders responses
- flag business transactions in controllers
- flag `DB::transaction` in controllers
- flag direct multi-model business workflows in controllers

FormRequest usage:
- non-trivial validation belongs in a FormRequest
- request-level authorization may live in a FormRequest when appropriate
- request input preparation is allowed when it shapes input before validation
- flag persistence inside FormRequests
- flag business workflows inside FormRequests

Action per use case:
- each Action should represent one business use case
- Action coordinates model writes
- Action owns transactional operations
- flag Actions that become broad service objects
- flag god services that collect unrelated use cases

Read/query placement:
- simple read-only pages may query in controllers if small
- non-trivial reads, filters, pagination, reused queries, or large mapping should move to Query/read class
- do not create query classes for trivial reads

Transaction placement:
- transactions belong in Actions for business operations
- use `DB::transaction` when one business operation writes multiple records
- do not place transactions in controllers

Active workshop scoping:
- do not use direct `user.workshop_id`
- resolve workshop access through `WorkshopUser`
- scope queries by active workshop membership
- scope authorization by active workshop membership

Frontend structure:
- page components orchestrate layout and props
- feature components own tables, lists, cards, badges, and modals
- no stores for page-local Inertia props
- use TypeScript types for DTO/props

Architecture posture:
- do not accept "quick MVP now, clean later" as an architecture excuse
- prefer simple but correct architecture from the beginning
