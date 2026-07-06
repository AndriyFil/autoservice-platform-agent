# Product Roadmap & UI/UX Plan

> Status: strategy document, written 2026-07-06. Builds on [market-and-business.md](market-and-business.md) (competitive gaps) and assumes the current MVP state: chat-first intake, booking requests, repair orders + lines, versioned estimate PDFs, customers/vehicles, multi-workshop tenancy. Guiding principle stays the same as the product vision: prefer customer simplicity over exposing internal complexity, and keep the LLM extraction-only (`docs/specs/chat-first-intake.md`).

Each phase ends in a demoable, self-contained slice. Order within a phase = build order.

---

## Phase A — Close the loop

The intake loop is currently broken at both ends: the workshop learns about new requests only by polling the dashboard, and the customer hears nothing after submitting. These four items make the existing MVP actually usable end-to-end, and three of them are 2026 table stakes in every Western competitor.

### A1. Workshop notifications on new booking request
Email (and later Telegram) notification to workshop staff when a booking request arrives. Without this, time-to-first-response — the KPI the whole product sells — is unmeasured and unbounded. Requires a real mail driver and queued jobs (see [tech-debt-and-agents.md](tech-debt-and-agents.md) §7).

### A2. Estimate approval by link
Customer receives a magic link → mobile page listing estimate lines → taps approve/decline per line → workshop dashboard updates. Industry standard everywhere (Tekmetric ships it on its cheapest tier); reuses the existing estimate/estimate-line domain and PDF snapshot model. This is the single highest-value feature for a portfolio demo: it closes the money loop visually.

### A3. Customer status page
Same magic-link mechanism: request received → confirmed → in progress → done. Kills the "call to ask if the car is ready" loop — the most-praised feature category in GaragePlug reviews. No customer account, no login: phone number is identity, links are capability tokens.

### A4. Telegram bot intake channel
Second intake channel behind the same `IntakeExtractorInterface` (`app/Support/Intake/IntakeExtractorInterface.php`) — the boundary was designed for exactly this. Telegram reaches ~92% of UA internet users weekly; this converts the chat widget from "a web form that talks" into "the place customers already are." Per-workshop bot config lives in workshop settings.

## Phase B — Manager daily driver

Make the dashboard the tool the owner opens every morning, not just when a request arrives.

- **B1. Appointment calendar** (existing roadmap Phase 2): day/week view, bookings created from confirmed requests, drag to reschedule.
- **B2. Service catalog / canned services** (existing roadmap Phase 2): predefined services with default lines → repair order pre-filled in one click. Directly answers the "everything is 3+ clicks" complaint class that reviews punish hardest.
- **B3. Kanban status board for repair orders**: columns = `RepairOrderStatus` values, drag to transition, same actions as today's buttons. Status-driven boards with automatic customer notifications (via A3) are the most-loved pattern in the Western set.

## Phase C — Money features

- **C1. DVI-lite**: technician uploads photos to a repair order; photos appear on the A2 approval page next to the relevant lines. Photo evidence is the mechanism behind the +15–30% average-repair-order numbers in the DVI category — trust shifts from "believe the mechanic" to "see for yourself." Reuses `WorkshopDocumentStorage`.
- **C2. Payment QR** (Monobank/PrivatBank) on the approval/completion page — Appointer already treats this as standard for UA.
- **C3. Review request** after completion: link to the workshop's Google profile, sent via the A3 status page. Review automation is a top-cited AutoLeap retention feature.

Explicitly out of scope (unchanged from product docs): inventory, payroll, invoicing/accounting, multi-branch — RO App owns ops breadth; we don't chase it.

---

## UI/UX plan

### Customer side (mobile-first, zero login)
- **Chat widget polish**: typing indicator while extraction runs; quick-answer chips for structured follow-ups (time-slot suggestions, yes/no) so the "next missing field" question is a tap, not typing; visible "you'll be contacted by the workshop to confirm" expectation-setting.
- **Zero-login identity**: phone number is the customer key (already the domain rule); all customer-facing pages (status, approval) are magic links — signed, expiring URLs. No passwords, no accounts, ever.
- **Language**: auto-detect uk/pl/en from the customer message; widget chrome follows.

### Manager side
- **One-screen triage**: booking request view shows extracted fields + original message + LLM summary side by side; confirm-and-create-repair-order is one primary action (≤2 clicks from notification to repair order).
- **Kanban board** (B3) as the default repair-orders view; list view stays for search/filter.
- **Mobile-usable dashboard**: owners triage from the pit or the parking lot; every dashboard action must work on a phone viewport. Not a native app — responsive web is sufficient at this stage.

### Onboarding (first-week experience = retention feature)
Onboarding friction is the #1 churn complaint across all five Western competitors. Concretely:
- **Demo data** seeded on workshop creation (sample request, repair order, estimate) so every screen shows its purpose immediately; one-click purge.
- **Setup checklist** on the dashboard: add services → put the link in Instagram bio → print the QR poster → first real request.
- **QR poster generator**: printable "scan to book" A4/A5 PDF with the workshop's intake URL — reuses the existing PDF pipeline, doubles as a growth artifact.
