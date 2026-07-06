# Tech Debt, Code Risks & Agent Proposals

> Status: strategy document, written 2026-07-06, from a structural codebase survey. Companion to [market-and-business.md](market-and-business.md) and [product-roadmap-ux.md](product-roadmap-ux.md). Priorities below are ordered by (leak/security risk first, then production readiness); several items are prerequisites for roadmap Phase A.

---

## 1. What is already done well — do not churn

- **Schema-level tenancy**: every tenant table carries `workshop_id` with proper FK constraints and composite indexes (`[workshop_id, status]`, `[workshop_id, opened_at]` on `repair_orders`).
- **Tenancy isolation tests exist** (`tests/Feature/DashboardTest.php` — cross-workshop scoping and session-repair tests).
- **LLM resilience**: `LlmIntakeExtractor` catches all failures and falls back to `ManualFallbackIntakeExtractor`; message capped at 4,000 chars; honeypot + phone normalization on public intake.
- **Layering discipline**: Controller → FormRequest → Action → Model holds; Query classes eager-load correctly (no N+1s found).
- **Critical transactions**: estimate/PDF path uses `DB::transaction` with `lockForUpdate`.

## 2. Code issues, priority order

### 2.1 Tenancy hardening (High)
Workshop isolation relies on each Action/Query remembering to add `where('workshop_id', ...)` (e.g. `app/Actions/RepairOrders/CreateRepairOrderAction.php` does it in three places). One forgotten clause = cross-tenant data leak, the worst failure mode this product has.

Fix: `BelongsToWorkshop` trait applying a global scope bound to the active workshop (resolved via `app/Support/ActiveWorkshopMembershipResolver.php`) on all tenant models; **keep** the explicit action-level checks as a second layer; add a cross-tenant leak test per resource (pattern already exists in `DashboardTest`).

### 2.2 Authorization (Medium)
`app/Enums/WorkshopUserRole.php` defines `Owner`/`Staff`, but no Policy, Gate, `authorize()` or `can()` call exists anywhere — both roles can do everything. Fix: Laravel Policies; minimum rule set: only owners manage workshop settings and members.

### 2.3 LLM cost control (Medium — blocks any public launch)
`app/Support/Intake/LlmIntakeExtractor.php` has no per-workshop quota, no cost tracking, no alerting; the public route only has `throttle:10,1`. Repeated 4,000-char submissions burn OpenAI budget silently. Fix: per-workshop daily quota, an LLM-call cost log, alert on fallback-rate spike. (Also the enforcement point for the free tier's "30 intakes/mo".)

### 2.4 Transaction coverage (Medium)
`app/Actions/BookingRequests/ChangeBookingRequestStatusAction.php` and `app/Actions/RepairOrders/CompleteRepairOrderAction.php` mutate state without `DB::transaction`. Wrap them (and audit the remaining Actions) to match the pattern already used in the estimate path.

### 2.5 Audit logging (Medium)
Two `Log::` calls in the whole app. No trail for status transitions, estimate generation, or membership changes — undebuggable in production and a blocker for the GDPR/data-protection risk in the business doc. Fix: lightweight `audit_logs` table written from Actions (who, workshop, action, subject, before → after).

### 2.6 Soft deletes (Low-Medium)
No soft deletes anywhere; deleting a workshop cascades everything irreversibly. Add `softDeletes()` to `Customer`, `Vehicle`, `BookingRequest`, `RepairOrder` — also required for a sane data-retention story.

### 2.7 Async + mail (Medium — prerequisite for roadmap A1)
`QUEUE_CONNECTION=database` but PDF generation runs synchronously in-request, and `MAIL_MAILER=log`. Queue PDF generation, configure a real mail driver — notifications (A1) depend on both.

### 2.8 Frontend component size (Low)
`resources/js/pages/Dashboard/BookingRequests/Show.vue` is 335 lines (status dialogs + extraction display + actions in one file). Extract child components into `components/booking-requests/` per the established feature-component pattern. `RepairOrders/Create.vue` (~200 lines) is borderline — split only when next touched.

Not worth doing now: admin panel (no ops team), billing integration (no paying users — but keep the quota hooks from 2.3 billing-shaped), distributed tracing (Telescope locally is enough).

## 3. Dev workflow skills/agents to add (`.agents/skills/`)

Existing nine skills (controller flow, frontend component flow, frontend structure, learning note, task workflow, testing strategy, caveman dev mode, chat-first UX flow, GRASP/SOLID review) — no overlaps below.

| Skill | Purpose | Trigger |
|---|---|---|
| `tenancy-leak-review` | Checklist review of any diff touching Queries/Actions/Models: every query workshop-scoped? route-model binding verified against active workshop? new tables have `workshop_id` + index? | Every PR touching data access; pairs with 2.1 |
| `llm-boundary-guard` | Reviews intake/AI changes against the forbidden list in `docs/specs/chat-first-intake.md` (no diagnosis, no repair recommendations, no pricing, no availability promises); checks prompt changes and output mapping | Any change under `app/Support/Intake/` |
| `pest-test-scaffold` | Generates feature-test skeletons matching house patterns: two-workshop leak fixture, role matrix, status-transition table | New endpoint/Action |
| `migration-safety-check` | Reviews migrations: reversible? destructive change flagged? FK delete behavior explicit? index on new query paths? enum-value removal handled? | Any new migration |
| `ux-copy-i18n` | Keeps `lang/en|pl|uk` in sync: finds keys missing per locale, flags hardcoded strings in Vue components (translations currently only cover repair orders/estimates) | Any user-facing string change |

## 4. Product AI features (all within the extraction-only boundary)

Ordered by leverage; every item keeps LLM usage behind `app/Support/Intake/` per the architecture rule.

1. **Telegram/Viber bot intake** — same `IntakeExtractorInterface`, new channel adapter (roadmap A4). The single most market-relevant AI investment.
2. **Voice-message transcription** — UA customers heavily use voice notes; Whisper (or similar) transcribes, then the *existing* extractor runs on the text. No new LLM permissions needed.
3. **Photo attachment intake** — customer attaches a photo (dashboard warning light, damage); stored on the booking request for staff. Explicitly *attach only, never interpret* — interpretation would cross the no-diagnosis line.
4. **Auto language detection** — detect uk/pl/en from the first message; reply questions and widget chrome follow.
5. **Staff daily digest** — morning summary of new/stale requests using the already-allowed summarization capability.
6. **Guardrail eval suite** — test-prompt set proving the bot refuses diagnosis, repair recommendations, and price questions in all three languages; run in CI on any prompt change. Turns the safety posture from a claim into a demonstrable artifact (strong portfolio piece).

## 5. Suggested sequencing

1. **2.1 tenancy trait + leak tests** and **2.4 transactions** — cheap, highest risk-reduction, no product visibility needed.
2. **2.7 queue + mail** → unblocks roadmap **A1 notifications**.
3. **2.3 LLM quotas** before any public exposure beyond the demo.
4. **2.2 policies**, **2.5 audit log**, **2.6 soft deletes** alongside Phase A feature work.
5. Skills from §3 as each area is next touched (write `tenancy-leak-review` first — it guards item 1).
