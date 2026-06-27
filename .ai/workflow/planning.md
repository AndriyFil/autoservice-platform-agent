# Planning Workflow

Planning keeps the work small enough to finish cleanly.

## Before Planning

For non-trivial tasks, read `.ai/lessons/autoservice.md` first and apply any relevant lessons.

If the user provides a Task Packet, use it as the source of truth. If the request is clear but not in Task Packet format, convert it into a short working summary.

## Planning Checklist

Ask these questions before editing:

- What is the requested outcome?
- What files are likely affected?
- Is this backend, frontend, product UX, documentation, or review work?
- What is the smallest useful slice that satisfies the request?
- What is explicitly out of scope?
- What verification is allowed and appropriate?

## Architecture Questions

For implementation tasks, check:

- Is this one business use case or shared domain behavior?
- Will public and dashboard flows duplicate the same rule?
- Does this need an Action, Query, FormRequest, or only controller orchestration?
- What should the reviewer verify before acceptance?

## Output

Keep plans short. A useful plan names the slice, the files, and the verification approach. Do not restate the full user request unless it needs clarification.

If the task is documentation-only, the plan should say so and avoid application-code changes.
