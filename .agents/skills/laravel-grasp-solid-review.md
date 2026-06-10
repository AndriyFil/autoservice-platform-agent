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
5. Fat controllers
6. God services
7. Duplicated logic
8. Complex queries in wrong layer
9. N+1 queries
10. Over-engineering

## Red flags

- Controller contains business decisions.
- FormRequest contains business workflow.
- Service has many unrelated public methods.
- Repository only wraps simple Eloquent calls.
- DTO exists only to move three fields once.
- Interface has only one implementation without a real reason.
- Pattern is added before there is pain.

## Output format

When reviewing, return:

1. Critical issues
2. Architectural issues
3. Simpler alternative
4. Suggested file-level changes
5. Tests to add or update

## AutoService Architecture Checklist

Review against the accepted project flow:

Controller -> FormRequest -> Action -> Model/DB

Controller thinness:
- controller performs HTTP orchestration only
- controller calls FormRequest and Action
- controller may write session only for HTTP/session concerns
- controller redirects or renders responses
- flag business transactions in controllers
- flag `DB::transaction` in controllers
- flag direct multi-model business workflows in controllers

FormRequest usage:
- non-trivial validation belongs in a FormRequest
- request-level authorization may live in a FormRequest when appropriate
- flag persistence inside FormRequests
- flag business workflows inside FormRequests

Action per use case:
- each Action should represent one business use case
- Action coordinates model writes
- Action owns transactional operations
- flag Actions that become broad service objects
- flag god services that collect unrelated use cases

Transaction placement:
- transactions belong in Actions for business operations
- use `DB::transaction` when one business operation writes multiple records
- do not place transactions in controllers

Active workshop scoping:
- do not use direct `user.workshop_id`
- resolve workshop access through `WorkshopUser`
- scope queries by active workshop membership
- scope authorization by active workshop membership

No god services:
- reject broad services with unrelated responsibilities
- prefer explicit Actions for use cases
- accept an abstraction only when it reduces complexity, duplication, unclear responsibility, risk of change, or testing difficulty

Architecture posture:
- do not accept "quick MVP now, clean later" as an architecture excuse
- prefer simple but correct architecture from the beginning
