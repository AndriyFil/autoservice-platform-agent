# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

AutoService is a multi-workshop SaaS booking/repair-order platform. Laravel 12 + Inertia (Vue 3) + TypeScript. Chat-first customer intake: an LLM extracts structured booking data from free-text customer messages, never diagnoses vehicles, recommends repairs, or estimates prices.

## Commands

```bash
# Dev (server + queue + logs + vite, all concurrently)
composer dev

# Frontend only
npm run dev
npm run build
npm run build:ssr

# Lint / format
npm run lint            # eslint --fix
npm run format          # prettier --write resources/
npm run format:check

# PHP tests
php artisan test
php artisan test --filter=TestClassName
php artisan test tests/Feature/EstimateDocumentManagementTest.php
vendor/bin/pint          # PHP code style (Laravel Pint)

# PHP static analysis (larastan, level 5, see phpstan.neon)
vendor/bin/phpstan analyse --memory-limit=1G
```

Frontend has no separate test runner configured; type-check via `vue-tsc` (used internally by `vite build`).

Agents must NOT run Docker, composer/npm install-update, migrations, or service startup unless the user explicitly asks — see Workflow Rules below.

## Workflow Rules (from AGENTS.md — read it in full before non-trivial work)

- Check `.ai/lessons/autoservice.md` before planning non-trivial tasks.
- For non-trivial tasks: plan → smallest useful slice → implement → verify → update `.ai/task-report.md` → stop.
- Workflow docs live in `.ai/workflow/` (README, planning, execution, verification, lessons).
- Role-specific skills live in `.agents/skills/` (controller flow, testing strategy, frontend structure, chat-first UX, GRASP/SOLID review, learning notes, task workflow).
- Scope discipline: implement only the requested task; no speculative abstractions or unrelated refactors — note ideas in Follow Ups instead.

## Architecture

### Backend request flow (mandatory)

```
Controller -> FormRequest -> Action -> Model/DB
```

- **Controller**: HTTP orchestration only. No business transactions, no `DB::transaction`, no direct multi-model workflows. One controller action per route intent (this codebase favors many small single-action-ish controllers, e.g. `CompleteDashboardRepairOrderController`, `CancelDashboardRepairOrderController`, `EstimateDashboardRepairOrderController`, rather than fat resourceful controllers).
- **FormRequest** (`app/Http/Requests/`): validation + request-level authorization + input shaping only. No persistence.
- **Action** (`app/Actions/<Domain>/`): one business use case, owns transactions, coordinates model writes. Organized by domain: `BookingRequests/`, `RepairOrders/`, `Estimates/`, `Workshops/`.
- **Query classes** (`app/Queries/<Domain>/`): non-trivial reads, eager loading, filtering/pagination, DTO/presentation mapping. Don't create one for a trivial read that stays clear inline in the controller.
- **Model** (`app/Models/`): relationships, casts, fillable/guarded, scopes, enum behavior. No business workflows.
- **Policy**: only when a feature needs authorization beyond a simple route/auth guard.

### Multi-tenancy: Active Workshop

Everything is workshop-scoped. Users belong to workshops via `WorkshopUser` (many-to-many with a `role`: `owner`/`staff` — `WorkshopUserRole` enum). There is no `workshop_id` on `users`.

- Never use `user.workshop_id` directly.
- Resolve access through `WorkshopUser` / `ActiveWorkshopMembershipResolver` (`app/Support/ActiveWorkshopMembershipResolver.php`).
- Active workshop id lives in the session; the `EnsureActiveWorkshop` middleware (`app/Http/Middleware/EnsureActiveWorkshop.php`) guards dashboard routes.
- All dashboard queries/actions must be scoped by active workshop membership — this is the #1 thing reviewers check for (cross-workshop leaks).

### Core domain (see `docs/architecture/adr-001-mvp-domain-database-model.md` and `docs/product/domain-model.md`)

`Workshop` → has many `Customer`, `Vehicle`, `BookingRequest`, `RepairOrder` (via customer/vehicle). `RepairOrder` has many `RepairOrderLine` (typed via `RepairOrderLineType` enum) and can generate an `Estimate` (with `EstimateLine`s) and PDF `Document`s. Status enums drive workflow: `BookingRequestStatus`, `RepairOrderStatus`, `EstimateStatus`, `DocumentStatus`/`DocumentType`.

Public-facing flow: `w/{workshop:slug}` → `PublicIntakeController` (chat-first intake) and `PublicBookingRequestController`. Dashboard flow: `dashboard/*` routes behind `auth` + `EnsureActiveWorkshop`, one prefix per resource (`booking-requests`, `repair-orders`, with nested `lines`, `estimate`, `complete`, `cancel` sub-actions).

### Chat-first intake boundary (`app/Support/Intake/`)

LLM usage is restricted to: extracting structured intake data, summarizing the customer message, detecting missing required fields, asking the next missing question. It must never diagnose, recommend repairs, estimate prices, or act as a general chatbot.

- `IntakeExtractorInterface` — contract; swap implementation without touching controllers.
- `LlmIntakeExtractor` — OpenAI-backed extraction, mapped via `OpenAiIntakeExtractionResultMapper`.
- `ManualFallbackIntakeExtractor` — fallback when LLM extraction is unavailable/fails.
- `MissingNextIntakeFieldResolver` + `MissingNextIntakeFieldRule` implementations (`MissingPhoneIntakeFieldRule`, `MissingPreferredTimeIntakeFieldRule`, `MissingVehicleIntakeFieldRule`) — pluggable rules for "what to ask next," keyed off `MissingIntakeField` enum.

Keep any future LLM integration behind this Support/Intake boundary — do not spread AI calls into controllers or components.

### Documents/PDF

`WorkshopDocumentStorage` (`app/Support/Documents/`) centralizes workshop-scoped file storage; `Document` model + `DocumentType`/`DocumentStatus` enums track generated artifacts (e.g. estimate PDFs via `GenerateEstimatePdfAction`, rendered from `resources/views/pdf/estimates/show.blade.php`, downloaded via `DashboardDocumentDownloadController`). Configured in `config/documents.php`.

### Frontend (Inertia + Vue 3 + TypeScript)

- `resources/js/pages/` — Inertia page components, organized to mirror route groups (`Dashboard/`, `Customers/`, `PublicBookingRequests/`, `onboarding/`, `settings/`, `auth/`). Pages orchestrate layout and pass props only.
- `resources/js/components/<feature>/` — feature components (tables, lists, cards, modals) extracted out of pages; e.g. `components/repair-orders/`, `components/booking-requests/`, `components/dashboard/`. Modals live in `components/dashboard/modals/`-style subfolders near their feature.
- `resources/js/components/ui/` — shared primitives (radix-vue/shadcn-style), reused across features only.
- `resources/js/composables/` — e.g. `useTranslations.ts` for i18n (`lang/en|pl|uk/*.php` backing translation strings, exposed to Inertia via `HandleInertiaRequests` middleware).
- `resources/js/layouts/` — `app/`, `auth/`, `settings/` layout shells.
- Don't introduce Pinia/stores for page-local server props; reserve shared state stores for cross-page concerns (auth user, active workshop, theme, locale, sidebar, notifications).
- Bulky/reusable TypeScript types go in feature-local `types.ts` (see `components/repair-orders/types.ts`), not inlined in components.

## Docs worth reading before larger features

- `docs/architecture/adr-001-mvp-domain-database-model.md` — DB/domain model decisions.
- `docs/product/domain-model.md`, `docs/product/business-rules.md`, `docs/product/mvp-scope.md`, `docs/product/vision.md`, `docs/product/roadmap.md` — product context.
- `docs/specs/chat-first-intake.md`, `docs/product/chat-first-intake.md` — intake flow spec.
- `docs/learning/` — project-specific Laravel/Vue/Inertia learning notes (e.g. `vue-inertia-page-props.md`).
