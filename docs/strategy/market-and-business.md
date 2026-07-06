# Market & Business Plan

> Status: strategy document, written 2026-07-06. Market data from live web research (sources cited inline; uncertain claims marked). The business plan is intentionally framed as a realistic-but-hypothetical exercise — AutoService is currently a portfolio/learning project, and this document describes how it *would* be taken to market.

Companion docs: [product-roadmap-ux.md](product-roadmap-ux.md), [tech-debt-and-agents.md](tech-debt-and-agents.md).

---

## 1. Competitive landscape

### 1.1 Ukraine / CEE

| Product | Origin / positioning | Pricing | Customer-facing UX | Weaknesses vs us |
|---|---|---|---|---|
| **RO App** (ex-RemOnline) | UA origin, biggest CIS/CEE player; full ops suite (work orders, inventory, payroll, VIN lookup, e-signature estimates) | Hobby €15/mo (capped: 2 staff, 100 orders/30d), Startup €29, Business €69 (full features), Enterprise €99 ([roapp.io/pricing](https://roapp.io/pricing/)) | Online booking calendar; unified messenger inbox (Telegram, Viber, Instagram, WhatsApp, SMS, email) — but **human-triaged**, staff still read and reply ([remonline.ua/crm-for-messengers](https://remonline.ua/crm-for-messengers/)) | Usable tiers €29–69 are steep for a 2–4 person UA shop; booking is a form/calendar, not conversational; general-service DNA (also phone repair etc.) |
| **HelloClient** | CIS-founded generic repair-shop tool, 5,000+ service centers in 25+ countries | $15/mo per location, unlimited users ($12.75 annual) ([helloclient.app/en/pricing](https://helloclient.app/en/pricing)) | Order-status widget + SMS/email notifications; **no self-booking, no chat intake** | No VIN, no auto-specific depth; RU/CIS lineage is a real post-2022 sentiment risk with UA buyers (commercial impact uncertain) |
| **Appointer** | UA CRM for СТО/tire shops | ₴560–700/mo (~$14–17), 14-day trial ([appointer.ua](https://appointer.ua/programa-dlya-sto/)) | 24/7 online self-booking; staff chat with clients via Telegram & Instagram inside the CRM; Mono/PrivatBank QR payments, Checkbox fiscal receipts | Form-based booking; no structured extraction; smaller feature set |
| **EasyWeek** | Booking-first multi-vertical CRM | Free plan (booking + calendar + client DB); paid from ~$8–9/mo ([easyweek.com.ua](https://easyweek.com.ua/business/solutions/car-service)) | Strong 24/7 booking widget, automated reminders | Not auto-specific; no repair orders/estimates depth |
| **Integra Car 7** (PL) | Polish legacy incumbent, desktop, distributed via Inter Cars | Perpetual license, "several to over a dozen thousand PLN" one-off ([integra.com.pl](https://integra.com.pl/programy-dlya-serwisow-i-warsztatow/samochodowych-integra-car-7/)) | None modern — no customer chat/booking; deep internal features (repair-time catalogs, KSeF e-invoicing for the 2026 PL mandate) | Expensive up front, legacy UX; vulnerable to cloud challengers on customer experience |

Products searched but not verifiable as active UA players: Autopark, CarX, STO-Soft (likely defunct or marginal — uncertain).

### 1.2 Western benchmark (feature ceiling, not direct competitors)

| Product | Entry price | Killer features | Common complaints |
|---|---|---|---|
| **Shopmonkey** | $215/mo annual ([pricing](https://www.shopmonkey.io/pricing)) | Ease of use, strong 2-way text/email, integrated payments incl. BNPL | Expensive for micro shops; price hikes; weaker labor catalogs |
| **Tekmetric** | $199/mo ([pricing](https://www.tekmetric.com/pricing)) | Built-in DVI on all tiers, "Smart Jobs" fast RO creation, 30+ parts integrations | Learning curve ("everything is 3+ clicks"); texting gated to top tier |
| **AutoLeap** | ~$179/mo (reported by review sites, not published — uncertain) | DVI to customer phone, approve-from-phone, Google Reviews automation | Slow onboarding; contract lock-in complaints |
| **Shop-Ware** | $249/mo flat | DVX ecommerce-style inspection page; claims 89% work-approval rate; AI Parts Matrix markup optimizer | Entry cost; admin learning curve |
| **GaragePlug** | ~$99/mo (GetApp figure — uncertain) | WhatsApp/SMS/email updates at every repair stage, branded customer app | Priced above local competitors in its markets; weak accounting |

**2026 table stakes** (every serious Western player has these): DVI photo inspections · estimate approval by link with per-line tap approve/decline + e-signature · 2-way texting inside the repair-order screen · payment links · VIN lookup · status notifications on every transition · online scheduling.

**Why DVI matters:** shops see 15–30% higher average repair order after adopting photo inspections; inspections with 20+ photos carry 30.4% higher RO value; Tekmetric claims 2x estimate-approval rate vs verbal-only (vendor data — marketing-grade, not independent research) ([AutoVitals](https://www.autovitals.com/solutions/digital-vehicle-inspections/), [Victory Auto case study](https://blog.autovitals.com/victory-auto-service-increases-aro-across-28-locations-with-autovitals)).

### 1.3 AI feature landscape 2024–2026

- **AutoLeap AIR** (April 2026): "first AI receptionist for auto shops" — answers missed/after-hours *calls*, captures customer + vehicle details, books appointments. $1/call or $99–199/mo. Their stated justification: 20–30% of inbound service calls go unanswered; 80% of callers don't leave voicemail ([BusinessWire](https://www.businesswire.com/news/home/20260428092601/en/AutoLeap-Introduces-the-First-AI-Receptionist-for-Auto-Shops-AutoLeap-AIR)). This is the closest analog to our intake — but voice, not chat.
- **Shop-Ware AI Parts Matrix**: ML margin optimization, back-office only.
- **RO App**: advertises "auto-reply to messages" in its inbox — scope unclear (uncertain), watch closely.
- **No competitor, regional or Western, does LLM-structured extraction of booking data from free-text chat.** The AI race is on voice receptionists and back-office optimization. Chat-first text intake is genuinely unoccupied space.

---

## 2. Positioning

**One-liner:** *The customer writes like a human; the workshop gets a structured booking.*

The pain is documented, not hypothetical: UA vendors already sell "missed-call control" and IP-telephony integrations — i.e., the market's current answer to lost bookings is *logging the failure*. We attack the intake step itself.

Channel fit is exceptional for Ukraine:

- Telegram reaches ~92% of Ukrainian internet users weekly ([Gradus media research](https://gradus.app/en/open-reports/research-media-consumption-ukraine/), [DataReportal Digital 2026 Ukraine](https://datareportal.com/reports/digital-2026-ukraine)).
- Kyiv workshops already take bookings via Instagram DM (oiler.ua, atl.ua, nesemos_autohub and others — observed live accounts).
- 44% of Ukrainians spend 3+ hours/day in messengers.

Differentiation vs each competitor class:

- **vs RO App:** they aggregate chats for staff to read; we *answer and structure* the chat. We win on intake automation and price; they win on ops breadth — so we do not compete on inventory/payroll, we integrate or coexist.
- **vs HelloClient:** we win on auto-specific depth (vehicle-aware intake, estimates, VIN later) at comparable price.
- **vs Appointer/EasyWeek:** they offer forms and calendars; free-text + LLM extraction removes the form-filling friction their widgets still impose.
- **Moat honesty:** the LLM layer itself is replicable. The durable edge is speed, auto-specific extraction quality, and the extraction-only trust posture (never diagnoses, never quotes prices — see `docs/specs/chat-first-intake.md`), which workshops can safely put in front of customers.

---

## 3. Business plan (hypothetical, portfolio framing)

### ICP
Independent Ukrainian СТО with 1–5 staff, phone-first today, active on Instagram/Telegram, no dedicated service advisor. Owner does triage between jobs — every missed call is lost revenue.

### Pricing
Market clears at roughly $10–20/mo for small UA shops (HelloClient $12.75–15, Appointer ~$14–17, RO App usable tiers €29+; free entry tiers are common). Proposed:

| Tier | Price | Contents |
|---|---|---|
| Free | $0 | 1 workshop, 30 intakes/mo, chat widget, dashboard, repair orders |
| Pro | ~$15/mo | Unlimited intakes, estimate-approval links, notifications, Telegram bot channel |
| Team | ~$29/mo | Multi-staff roles, calendar, kanban board, priority support |

Annual prepay discount 15% (regional norm). LLM cost per intake conversation is ~$0.01 at current model pricing — COGS is negligible; the free tier's real cost is support, not compute.

### Go-to-market
1. Direct outreach to Instagram-active workshops in Kyiv/Lviv (they already handle DM bookings — the pitch is "same behavior, zero manual triage").
2. 5–10 free pilots; collect intake-completion and missed-call-recovery numbers; convert to case studies.
3. "Scan to book" QR poster for the workshop counter as a built-in viral artifact.
4. Later: PL market entry (requires KSeF e-invoicing awareness and Integra displacement — separate effort, not MVP).

### KPIs
- **Intake completion rate** — % of started chats that end as a valid booking request (core product metric).
- **Booking→confirmed rate** — % of requests the workshop confirms (measures intake quality, not just volume).
- **Time-to-first-response** — workshop reaction time; notifications feature directly moves this.
- Activation: workshops with ≥5 intakes in first 14 days. Retention: monthly active workshops.

---

## 4. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **RO App ships an AI reply/intake layer** — they already own the messenger inbox plumbing | Medium-high | High | Speed; auto-specific extraction quality; stay the "safe" extraction-only option; don't compete on ops breadth |
| **LLM cost abuse** on the public intake endpoint (only `throttle:10,1` today) | High if launched as-is | Medium | Per-workshop daily quotas, cost logging, alerting — see [tech-debt-and-agents.md](tech-debt-and-agents.md) §3 |
| **Wartime willingness-to-pay** — small UA shops cut discretionary spend | Medium | Medium | Free tier as default entry; price in UAH; sell recovered bookings, not software |
| **Bot-trust objection** — customers may want a human, not a bot | Medium | Medium | Bot never pretends to be human, never diagnoses or quotes; staff confirms by phone (already a product rule); measure completion rate to validate |
| **Single-developer capacity** — roadmap breadth vs one person | High | Medium | Phased roadmap with demoable slices; ruthless scope discipline (already a project rule) |
| **Data protection** — UA personal-data law now; GDPR the moment PL launches | Low now, High on PL entry | High | Phone numbers are PII: retention policy, soft deletes, audit log, data-export/delete path before PL market |
