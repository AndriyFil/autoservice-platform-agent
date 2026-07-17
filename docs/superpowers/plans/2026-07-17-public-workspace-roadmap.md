# Public Workspace Roadmap Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` to implement the linked plans task-by-task. Repository rules require single-agent execution unless the user explicitly requests subagents.

**Goal:** Deliver the public AI-chat workspace first, accept it, and only then add secure request history.

**Architecture:** This roadmap is an ordered handoff for two independently testable plans. The Welcome workspace establishes the stable public shell; customer request history later reuses that shell and adds OTP-scoped backend reads.

**Tech Stack:** Laravel, Inertia.js, Vue 3, TypeScript, Tailwind CSS, Vitest, PHPUnit, PostgreSQL.

## Global Constraints

- Read `AGENTS.md`, `docs/architecture/autoservice-ddd-rules.md`, and `docs/superpowers/specs/2026-07-17-public-workspace-roadmap-design.md` before editing.
- Preserve the existing dirty worktree; never reset, restore, or overwrite unrelated changes.
- Work as a single agent. Do not dispatch subagents unless the user explicitly authorizes them.
- Use TDD and make only focused commits containing files from the active task.
- Do not install dependencies, run Docker, start services, or run migrations without user approval.
- PostgreSQL is the only supported database.
- Public intake still creates one workshop-scoped `BookingRequest`; it never creates a `RepairOrder`.
- AI must not diagnose, price, promise availability, or replace staff confirmation.

---

## Execution Order

- [ ] **Stage 0: Record the starting state**

  Run `git status --short` and save the output in the session notes. Confirm that current uncommitted homepage work is preserved.

- [ ] **Stage 1: Execute the Welcome workspace plan**

  Follow `docs/superpowers/plans/2026-07-17-welcome-workspace.md` from Task 1 through its acceptance checkpoint.

- [ ] **Stage 2: Stop for product acceptance**

  Show desktop and mobile Welcome states before and after the first valid Send. Do not begin history work until the user explicitly accepts Milestone 1.

- [ ] **Stage 3: Execute the request-history plan**

  After acceptance only, follow `docs/superpowers/plans/2026-07-17-customer-request-history.md` from Task 1 through its final checkpoint.

- [ ] **Stage 4: Final focused regression review**

  Review the combined diff for intake state loss, OTP data exposure, unscoped request reads, duplicate navigation, and unrelated changes. Report every validation command actually run.

## New-Session Start Prompt

Use this prompt in a fresh Codex session:

```text
Execute docs/superpowers/plans/2026-07-17-public-workspace-roadmap.md as a
single agent. Start with the Welcome workspace plan only. Preserve the dirty
worktree, use TDD and focused commits, and stop at the Milestone 1 acceptance
checkpoint. Do not start customer request history until I explicitly approve
the Welcome result.
```
