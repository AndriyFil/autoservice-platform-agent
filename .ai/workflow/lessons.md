# Lessons Workflow

Lessons turn reusable user corrections into project memory.

## When To Add A Lesson

Append a lesson when a user correction is reusable across future tasks.

Good lesson candidates:

- a repeated preference about workflow
- a project-specific architecture boundary
- a safe default the agent missed
- a naming, structure, or reporting convention
- a product scope rule that prevents future drift

Do not add lessons for one-off preferences that apply only to the current task.

## Where Lessons Go

Use `.ai/lessons/autoservice.md` for AutoService-specific lessons.

Each lesson should be short and actionable. Prefer this shape:

```md
## YYYY-MM-DD - Short Lesson Title

- Correction: What the user corrected.
- Lesson: What agents should do next time.
- Applies when: The kinds of tasks where this matters.
```

## How Agents Use Lessons

Before non-trivial work, agents must check `.ai/lessons/autoservice.md` and apply relevant lessons.

After receiving a reusable user correction, agents should append a lesson before completing the task report.

Lessons should reduce repeated corrections. They should not become a dumping ground for every conversation detail.
