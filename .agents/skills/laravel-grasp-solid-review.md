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
