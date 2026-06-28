# Architecture Foundation Rules

These rules apply to all AutoService agents.

## 1. Problem-First Architecture

Do not introduce patterns, abstractions, services, entities, events, or infrastructure because they look architectural.

Before proposing or implementing a design, identify:

- the concrete problem
- the current pain
- the expected change
- the trade-off

If there is no clear problem, do not add architecture.

## 2. Trade-Offs Are Required

Every architectural decision must explain what we gain and what we pay.

Prefer explicit trade-offs:

- maintainability vs speed
- flexibility vs complexity
- testability vs implementation time
- simplicity vs future extensibility

For the MVP, prefer maintainability, testability, and small vertical slices over large “perfect” architecture.

## 3. Maintainability And Testability

Code should be simple to change and simple to test.

Prefer:

- clear business names
- one use case per Action
- small request validation classes
- focused read Query classes for non-trivial reads
- tests around business boundaries and status transitions

Avoid large services, duplicated rules, hidden coupling, and controller-owned business workflows.

## 4. AI-Generated Code Must Be Reviewed

Do not blindly trust AI-generated code or structure.

AI output must be reviewed for:

- wrong responsibility placement
- bloated controllers
- fake abstractions
- unnecessary entities
- overengineering
- weak testability
- hidden coupling

If AI proposes a complex design, simplify it unless the complexity solves a real current problem.

## 5. No Overengineering

Patterns must pay rent.

Do not introduce:

- Chat entity
- Conversation entity
- AI Thread entity
- Workflow engine
- CQRS
- Event Sourcing
- microservices
- queues

unless a concrete current pain requires them.

For now, `BookingRequest` remains the intake aggregate.

## 6. Interfaces Only For Real Variation Points

Use interfaces when there is a real axis of change, external provider, or test seam.

Good examples:

- `IntakeExtractorInterface`
- AI provider abstraction
- notification sender abstraction
- payment provider abstraction in future

Bad examples:

- empty interfaces with no reason
- one-method abstraction created only to look clean
- generic manager/helper/service without clear responsibility

## 7. Protected Variations

When something is likely to change, isolate it behind a small contract.

In AutoService, likely variation points are:

- AI provider
- extraction strategy
- notification channel
- phone verification provider
- customer identity strategy

Do not expose these details directly to controllers or broad domain workflows.

## 8. Controller / FormRequest / Action Layering

Do not let UI, controllers, or infrastructure directly own business decisions.

Preferred flow:

```txt
Controller -> FormRequest -> Action -> Model/DB
```

Controllers orchestrate HTTP only.

FormRequests validate and authorize request input.

Actions execute one business use case and own business transactions.

Models describe persistence behavior, casts, scopes, and relationships.

Queries/read classes are useful for non-trivial reads, filtering, pagination, reusable reads, or presentation mapping.

## 9. Keep Responsibilities Meaningful

Avoid dumping business decisions into generic helpers.

Avoid large procedural services that manipulate passive data objects from the outside.

Prefer names that describe business intent:

Good:
- SubmitIntakeRequestAction
- IntakeExtractionResult
- IntakeExtractorInterface

Bad:
- IntakeHelper
- CommonService
- DataManager
- ProcessUtil

## 10. Tests Are Part Of Architecture

Every architecture boundary should be testable.

For intake work, prefer tests around:

- public request submission
- BookingRequest persistence
- original_message preservation
- extraction result mapping
- missingNextField priority
- OpenAI fallback behavior

## 11. Small Safe Iterations

Implement in small safe iterations.

Each iteration should:

1. solve one problem
2. change few files
3. add or update focused tests
4. run lightweight checks
5. report trade-offs and next step
