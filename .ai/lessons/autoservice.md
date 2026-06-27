# AutoService Lessons

Reusable corrections and project-specific operating lessons for AutoService agents.

Agents must check this file before non-trivial tasks and apply any relevant lesson. When the user gives a correction that will be useful again, append it here in the format below.

## Lesson Format

```md
## YYYY-MM-DD - Short Lesson Title

- Correction: What the user corrected.
- Lesson: What agents should do next time.
- Applies when: The kinds of tasks where this matters.
```

## 2026-06-25 - Normal Safe Work Should Not Require Special Mode Names

- Correction: The user should not need to type special execution mode names for normal safe agent work.
- Lesson: Agents may inspect and edit files needed for the requested task by default, while still asking before broad, destructive, dependency, service, migration, Docker, log, or external-state-changing commands.
- Applies when: Any agent is planning, implementing, verifying, or documenting ordinary scoped work.

## 2026-06-25 - Chat-First Intake Extraction

- Correction: Do not build fake-smart regex intake parsing.
- Lesson: Chat-first intake extraction is either AI-backed or a conservative manual fallback. Manual fallback may safely preserve original text and extract narrow, low-risk fields like phone, but must not pretend to understand vehicle, diagnosis, repair, price, or availability.
- Applies when: Implementing or reviewing customer intake extraction, fallback parsing, LLM boundaries, or public intake flows.

## 2026-06-25 - Reusable Corrections Become Lessons

- Correction: User corrections should become project lessons when they are reusable.
- Lesson: When a correction would prevent future mistakes across tasks, append a concise lesson to this file and mention it in `.ai/task-report.md`.
- Applies when: The user corrects workflow, architecture, scope, verification, reporting, product, or design behavior.

## 2026-06-26 - Public Landing Is Chat-First Only

- Correction: The public landing page must not show workshop cards or link to the old `/book/{slug}` workshop-specific flow.
- Lesson: Keep the public landing page focused only on chat-first intake: header, hero, intake textarea, example chips, submit state, and received state.
- Applies when: Implementing or reviewing the public landing page, chat-first intake entry point, or legacy workshop booking exposure.
