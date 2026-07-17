# Customer Request History Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Repository rules require single-agent execution unless the user explicitly requests subagents.

**Goal:** After Welcome acceptance, show OTP-protected booking-request history in the public sidebar and safe read-only request details in the central workspace.

**Architecture:** Customer-portal query classes scope every read to the normalized phone stored in the verified server session. Controllers orchestrate Inertia responses; focused Vue components render recent history, the full list, and read-only details inside the shared public workspace.

**Tech Stack:** Laravel, Eloquent, Inertia.js, Vue 3, TypeScript, Tailwind CSS, PHPUnit, Vitest, PostgreSQL.

## Global Constraints

- Do not start until the user explicitly accepts `2026-07-17-welcome-workspace.md`.
- Read `AGENTS.md`, `docs/architecture/autoservice-ddd-rules.md`, and `docs/superpowers/specs/2026-07-17-public-workspace-roadmap-design.md` before editing.
- Preserve the dirty worktree and all completed Welcome changes.
- Do not create a customer `User`, trust a phone or owner from the browser, or expose history before OTP verification.
- Scope list and detail reads by the exact normalized phone in `customer_portal.verified_phone`.
- Return 404 for a request outside that verified phone scope.
- Do not add a migration, event timeline, live chat, cancellation, editing, estimates, documents, or repair-order details.
- Work as a single agent with TDD, focused validation, and focused commits.

---

### Task 1: Customer-safe request index query

**Files:**
- Create: `app/Domain/CustomerPortal/Queries/CustomerRequestIndexQuery.php`
- Create: `tests/Feature/CustomerPortalRequestHistoryTest.php`

**Interfaces:**
- Consumes: normalized verified phone and existing `BookingRequest::workshop()` relation.
- Produces: `handle(string $verifiedPhone): array{recent: list<array>, hasMore: bool, requests: LengthAwarePaginator}`.

- [ ] **Step 1: Write failing direct-query tests**

Create requests for the verified phone and another phone, then assert that `handle('+380501112233')` returns only owned rows, newest first, maps status/workshop/dates, caps `recent` at 10, and sets `hasMore` when an eleventh row exists. Add an empty-result test.

Use this core fixture shape:

```php
$workshop = Workshop::factory()->create(['name' => 'Main Auto']);
$owned = BookingRequest::factory()->for($workshop)->create([
    'customer_phone' => '+380501112233',
    'problem_description' => 'Brake noise',
    'status' => BookingRequestStatus::New,
]);
BookingRequest::factory()->for($workshop)->create([
    'customer_phone' => '+380509999999',
    'problem_description' => 'Private request',
]);
```

- [ ] **Step 2: Run the direct query tests and verify failure**

Run: `php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php --filter=query`

Expected: FAIL because `CustomerRequestIndexQuery` does not exist.

- [ ] **Step 3: Implement exact-phone filtering and safe mapping**

```php
<?php

namespace App\Domain\CustomerPortal\Queries;

use App\Models\BookingRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRequestIndexQuery
{
    /** @return array{recent: array<int, array<string, mixed>>, hasMore: bool, requests: LengthAwarePaginator} */
    public function handle(string $verifiedPhone): array
    {
        $base = BookingRequest::query()
            ->where('customer_phone_normalized', $verifiedPhone)
            ->with('workshop:id,name')
            ->latest();

        $recentRows = (clone $base)->limit(11)->get();
        $requests = (clone $base)->paginate(20)
            ->through(fn (BookingRequest $request): array => $this->map($request));

        return [
            'recent' => $recentRows->take(10)
                ->map(fn (BookingRequest $request): array => $this->map($request))
                ->values()->all(),
            'hasMore' => $recentRows->count() > 10,
            'requests' => $requests,
        ];
    }

    /** @return array<string, mixed> */
    private function map(BookingRequest $request): array
    {
        $title = trim((string) ($request->problem_description ?: $request->original_message));

        return [
            'id' => $request->id,
            'title' => $title !== '' ? $title : 'Service request',
            'status' => ['value' => $request->status->value, 'label' => $request->status->label()],
            'workshopName' => $request->workshop->name,
            'submittedAt' => $request->created_at->toIso8601String(),
            'updatedAt' => $request->updated_at->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Run direct-query validation**

```bash
php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php --filter=query
vendor/bin/pint --test app/Domain/CustomerPortal/Queries/CustomerRequestIndexQuery.php tests/Feature/CustomerPortalRequestHistoryTest.php
```

Expected: direct query tests PASS and Pint reports no changes required.

- [ ] **Step 5: Commit the query**

```bash
git add app/Domain/CustomerPortal/Queries/CustomerRequestIndexQuery.php tests/Feature/CustomerPortalRequestHistoryTest.php
git commit -m "feat(portal): query customer requests"
```

### Task 2: Portal index endpoint

**Files:**
- Modify: `app/Http/Controllers/CustomerPortalController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/CustomerPortalRequestHistoryTest.php`
- Modify: `tests/Feature/CustomerPortalAccessTest.php`

**Interfaces:**
- Consumes: `CustomerRequestIndexQuery::handle(string $verifiedPhone)` and verified-session middleware.
- Produces: Inertia props `recentRequests`, `hasMoreRequests`, and `requests` for `CustomerPortal/Index`.

- [ ] **Step 1: Add failing route-level tests**

```php
public function test_verified_customer_receives_only_requests_for_the_verified_phone(): void
{
    $workshop = Workshop::factory()->create(['name' => 'Main Auto']);
    $owned = BookingRequest::factory()->for($workshop)->create([
        'customer_phone' => '+380501112233',
        'problem_description' => 'Brake noise',
    ]);

    $this->withSession($this->activeVerifiedSession('+380501112233'))
        ->get('/my-requests')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CustomerPortal/Index')
            ->has('recentRequests', 1)
            ->where('recentRequests.0.id', $owned->id)
            ->where('recentRequests.0.title', 'Brake noise')
            ->where('recentRequests.0.workshopName', 'Main Auto')
            ->where('hasMoreRequests', false));
}
```

Also assert empty history and that serialized props omit phone values and unrelated requests. Add a helper returning `customer_portal.verified_phone` and future `customer_portal.verified_until`.

- [ ] **Step 2: Run route tests and verify failure**

Run: `php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php --filter=verified_customer`

Expected: FAIL because the controller still renders the temporary page without props.

- [ ] **Step 3: Convert the invokable controller to an index action**

```php
public function index(Request $request, CustomerRequestIndexQuery $query): Response
{
    $verifiedPhone = (string) $request->session()->get('customer_portal.verified_phone');
    $history = $query->handle($verifiedPhone);

    return Inertia::render('CustomerPortal/Index', [
        'recentRequests' => $history['recent'],
        'hasMoreRequests' => $history['hasMore'],
        'requests' => $history['requests'],
    ]);
}
```

Also return the existing public navigation props from this controller:

```php
'canLogin' => Route::has('login'),
'canRegister' => Route::has('register'),
'adminLoginUrl' => AppUrl::adminPath('/login'),
'adminRegisterUrl' => AppUrl::adminPath('/register'),
```

Change the route to `[CustomerPortalController::class, 'index']`. Keep `EnsureVerifiedCustomerPhone` unchanged. Update the existing temporary-page test to expect the new empty props.

- [ ] **Step 4: Run portal backend tests**

```bash
php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php tests/Feature/CustomerPortalAccessTest.php
vendor/bin/pint --test app/Domain/CustomerPortal/Queries/CustomerRequestIndexQuery.php app/Http/Controllers/CustomerPortalController.php routes/web.php tests/Feature/CustomerPortalRequestHistoryTest.php tests/Feature/CustomerPortalAccessTest.php
```

Expected: tests PASS and Pint reports no changes required.

- [ ] **Step 5: Commit the index endpoint**

```bash
git add app/Http/Controllers/CustomerPortalController.php routes/web.php tests/Feature/CustomerPortalRequestHistoryTest.php tests/Feature/CustomerPortalAccessTest.php
git commit -m "feat(portal): expose request history"
```

### Task 3: Typed sidebar history

**Files:**
- Create: `resources/js/components/public-portal/types.ts`
- Create: `resources/js/components/public-portal/requestHistory.ts`
- Create: `resources/js/components/public-portal/CustomerRequestHistory.vue`
- Create: `resources/js/components/public-portal/CustomerRequestHistory.test.ts`
- Modify: `resources/js/layouts/PublicWorkspaceLayout.vue`

**Interfaces:**
- Consumes: `CustomerRequestSummary[]`, `hasMore`, and optional `selectedRequestId`.
- Produces: grouped links to `customer-portal.show` and a Show all link to `customer-portal.index`.

- [ ] **Step 1: Define types and failing grouping tests**

```ts
export type CustomerRequestStatus = { value: 'new' | 'confirmed' | 'rejected' | 'cancelled'; label: string };
export type CustomerRequestSummary = {
    id: number;
    title: string;
    status: CustomerRequestStatus;
    workshopName: string;
    submittedAt: string;
    updatedAt: string;
};
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
```

Freeze time and test Today, Last 7 days excluding Today, Earlier, truncation class, status/workshop text, selected `aria-current`, and conditional Show all.

- [ ] **Step 2: Run component tests and verify failure**

Run: `npm test -- resources/js/components/public-portal/CustomerRequestHistory.test.ts`

Expected: FAIL because the history component does not exist.

- [ ] **Step 3: Implement deterministic grouping and links**

```ts
export const requestGroup = (submittedAt: string, now = new Date()): 'Today' | 'Last 7 days' | 'Earlier' => {
    const submitted = new Date(submittedAt);
    const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const startSubmitted = new Date(submitted.getFullYear(), submitted.getMonth(), submitted.getDate());
    const ageDays = Math.floor((startToday.getTime() - startSubmitted.getTime()) / 86_400_000);
    if (ageDays <= 0) return 'Today';
    if (ageDays <= 6) return 'Last 7 days';
    return 'Earlier';
};
```

Render grouped lists in the `history` slot. Each link shows a one-line title, status, workshop, and date, and uses `aria-current="page"` for the selected request. Show all appears only when `hasMore` is true.

- [ ] **Step 4: Run focused frontend validation**

```bash
npx prettier --write resources/js/components/public-portal resources/js/layouts/PublicWorkspaceLayout.vue
npm test -- resources/js/components/public-portal/CustomerRequestHistory.test.ts resources/js/layouts/PublicWorkspaceLayout.test.ts
npx eslint resources/js/components/public-portal resources/js/layouts/PublicWorkspaceLayout.vue
```

Expected: tests PASS and ESLint exits 0.

- [ ] **Step 5: Commit sidebar history**

```bash
git add resources/js/components/public-portal resources/js/layouts/PublicWorkspaceLayout.vue resources/js/layouts/PublicWorkspaceLayout.test.ts
git commit -m "feat(portal): add sidebar history"
```

### Task 4: Full list, empty state, and OTP shell integration

**Files:**
- Modify: `resources/js/pages/CustomerPortal/Index.vue`
- Modify: `resources/js/pages/CustomerPortal.test.ts`
- Modify: `resources/js/pages/CustomerPortal/RequestAccess.vue`
- Modify: `resources/js/pages/CustomerPortal/VerifyCode.vue`
- Modify: `resources/js/layouts/CustomerPortalLayout.vue`
- Modify: `app/Http/Controllers/CustomerPortalAccessController.php`
- Modify: `tests/Feature/CustomerPortalAccessTest.php`

**Interfaces:**
- Consumes: paginated `requests`, `recentRequests`, and `hasMoreRequests`.
- Produces: portal access, verification, and history views inside `PublicWorkspaceLayout`.

- [ ] **Step 1: Write failing page tests**

Mount Index with one request and with an empty list. Assert it fills the history slot, links each full-list row, renders status/workshop/date, and shows `Create a new request` when empty. Assert RequestAccess and VerifyCode use `PublicWorkspaceLayout` through `CustomerPortalLayout`.

- [ ] **Step 2: Run page tests and verify failure**

Run: `npm test -- resources/js/pages/CustomerPortal.test.ts`

Expected: FAIL because Index is a temporary page and portal auth uses the old centered card shell.

- [ ] **Step 3: Render portal pages inside the shared shell**

Use this Index composition:

```vue
<PublicWorkspaceLayout
    :can-login="canLogin"
    :can-register="canRegister"
    :admin-login-url="adminLoginUrl"
    :admin-register-url="adminRegisterUrl"
>
    <template #history>
        <CustomerRequestHistory :requests="recentRequests" :has-more="hasMoreRequests" />
    </template>
    <section aria-labelledby="requests-title" class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 id="requests-title">Your requests</h1>
        <div v-if="requests.data.length === 0">
            <p>You do not have any service requests yet.</p>
            <Link :href="route('home')">Create a new request</Link>
        </div>
        <ul v-else>
            <li v-for="request in requests.data" :key="request.id">
                <Link :href="route('customer-portal.show', request.id)">
                    <span>{{ request.title }}</span>
                    <span>{{ request.status.label }} · {{ request.workshopName }}</span>
                </Link>
            </li>
        </ul>
        <nav v-if="requests.last_page > 1" aria-label="Request list pages">
            <template v-for="link in requests.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" :aria-current="link.active ? 'page' : undefined" v-html="link.label" />
                <span v-else aria-disabled="true" v-html="link.label" />
            </template>
        </nav>
    </section>
</PublicWorkspaceLayout>
```

Make `CustomerPortalLayout.vue` compose `PublicWorkspaceLayout` while preserving its default slot. Add the four navigation props to `CustomerPortalAccessController::create()` and `verifyCreate()` using `Route::has()` and `AppUrl::adminPath()`, and pass them through RequestAccess and VerifyCode. Keep OTP form validation, masking, expiration copy, and secure-verification copy unchanged. Never pass request history to access or verification pages.

- [ ] **Step 4: Run portal frontend regression tests**

```bash
vendor/bin/pint --test app/Http/Controllers/CustomerPortalAccessController.php tests/Feature/CustomerPortalAccessTest.php
npx prettier --write resources/js/pages/CustomerPortal resources/js/layouts/CustomerPortalLayout.vue resources/js/pages/CustomerPortal.test.ts
npm test -- resources/js/pages/CustomerPortal.test.ts resources/js/pages/AuthPublicDesign.test.ts resources/js/components/public-portal/CustomerRequestHistory.test.ts
npx eslint resources/js/pages/CustomerPortal resources/js/layouts/CustomerPortalLayout.vue resources/js/pages/CustomerPortal.test.ts
```

Expected: tests PASS and ESLint exits 0.

- [ ] **Step 5: Commit list UI**

```bash
git add app/Http/Controllers/CustomerPortalAccessController.php tests/Feature/CustomerPortalAccessTest.php resources/js/pages/CustomerPortal resources/js/layouts/CustomerPortalLayout.vue resources/js/pages/CustomerPortal.test.ts
git commit -m "feat(portal): render customer history"
```

### Task 5: Phone-scoped request detail endpoint

**Files:**
- Create: `app/Domain/CustomerPortal/Queries/CustomerRequestShowQuery.php`
- Modify: `app/Http/Controllers/CustomerPortalController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/CustomerPortalRequestHistoryTest.php`

**Interfaces:**
- Consumes: verified normalized phone and numeric request identifier.
- Produces: `handle(string $verifiedPhone, int $bookingRequestId): array<string, mixed>` or 404; Inertia `request` plus recent-history props.

- [ ] **Step 1: Write failing owned, foreign, and unverified detail tests**

```php
public function test_verified_customer_can_open_an_owned_request(): void
{
    $bookingRequest = BookingRequest::factory()->create(['customer_phone' => '+380501112233']);

    $this->withSession($this->activeVerifiedSession('+380501112233'))
        ->get("/my-requests/{$bookingRequest->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CustomerPortal/Show')
            ->where('request.id', $bookingRequest->id));
}

public function test_verified_customer_receives_404_for_another_phone_request(): void
{
    $bookingRequest = BookingRequest::factory()->create(['customer_phone' => '+380509999999']);

    $this->withSession($this->activeVerifiedSession('+380501112233'))
        ->get("/my-requests/{$bookingRequest->id}")
        ->assertNotFound();
}
```

Also assert an unverified GET redirects to OTP access.

- [ ] **Step 2: Run detail tests and verify failure**

Run: `php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php --filter=request`

Expected: FAIL with route not found.

- [ ] **Step 3: Scope before resolving and expose only safe fields**

```php
public function handle(string $verifiedPhone, int $bookingRequestId): array
{
    $request = BookingRequest::query()
        ->whereKey($bookingRequestId)
        ->where('customer_phone_normalized', $verifiedPhone)
        ->with('workshop:id,name')
        ->firstOrFail();

    return [
        'id' => $request->id,
        'title' => trim((string) ($request->problem_description ?: $request->original_message)) ?: 'Service request',
        'problemDescription' => $request->problem_description ?: $request->original_message,
        'status' => ['value' => $request->status->value, 'label' => $request->status->label()],
        'workshopName' => $request->workshop->name,
        'submittedAt' => $request->created_at->toIso8601String(),
        'updatedAt' => $request->updated_at->toIso8601String(),
        'customerName' => $request->customer_name,
        'vehicle' => array_filter([
            'brand' => $request->vehicle_brand,
            'model' => $request->vehicle_model,
            'year' => $request->vehicle_year,
            'licensePlate' => $request->vehicle_license_plate,
        ], static fn ($value): bool => $value !== null && $value !== ''),
    ];
}
```

Add the route after the index route:

```php
Route::get('my-requests/{bookingRequest}', [CustomerPortalController::class, 'show'])
    ->whereNumber('bookingRequest')
    ->middleware(EnsureVerifiedCustomerPhone::class)
    ->name('customer-portal.show');
```

Add a controller `show(Request $request, int $bookingRequest, CustomerRequestIndexQuery $indexQuery, CustomerRequestShowQuery $showQuery): Response` action. It obtains the phone only from session, calls both queries, and renders `CustomerPortal/Show` with `request`, `recentRequests`, and `hasMoreRequests`. Do not use implicit unscoped `BookingRequest` route binding.

- [ ] **Step 4: Run backend validation**

```bash
php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php tests/Feature/CustomerPortalAccessTest.php
vendor/bin/pint --test app/Domain/CustomerPortal/Queries app/Http/Controllers/CustomerPortalController.php routes/web.php tests/Feature/CustomerPortalRequestHistoryTest.php
```

Expected: tests PASS and Pint reports no changes required.

- [ ] **Step 5: Commit detail endpoint**

```bash
git add app/Domain/CustomerPortal/Queries/CustomerRequestShowQuery.php app/Http/Controllers/CustomerPortalController.php routes/web.php tests/Feature/CustomerPortalRequestHistoryTest.php
git commit -m "feat(portal): add request detail"
```

### Task 6: Read-only request detail workspace

**Files:**
- Create: `resources/js/components/public-portal/CustomerRequestDetail.vue`
- Create: `resources/js/components/public-portal/CustomerRequestDetail.test.ts`
- Create: `resources/js/pages/CustomerPortal/Show.vue`
- Modify: `resources/js/components/public-portal/types.ts`

**Interfaces:**
- Consumes: safe detail payload plus recent sidebar summaries.
- Produces: central read-only detail with Submitted and Current status/last updated milestones and no composer.

- [ ] **Step 1: Add the detail type and failing tests**

```ts
export type CustomerRequestDetail = CustomerRequestSummary & {
    problemDescription: string | null;
    customerName: string | null;
    vehicle: Partial<{ brand: string; model: string; year: number; licensePlate: string }>;
};
```

Assert full description, current status, workshop, submitted/updated dates, optional vehicle display, omission of empty rows, and absence of `textarea` or `Send message`.

- [ ] **Step 2: Run detail tests and verify failure**

Run: `npm test -- resources/js/components/public-portal/CustomerRequestDetail.test.ts`

Expected: FAIL because the component does not exist.

- [ ] **Step 3: Implement truthful read-only milestones**

```vue
<ol aria-label="Request progress">
    <li>Request submitted <time :datetime="request.submittedAt">{{ formatDateTime(request.submittedAt) }}</time></li>
    <li>
        Current status: {{ request.status.label }}
        <time :datetime="request.updatedAt">Last updated {{ formatDateTime(request.updatedAt) }}</time>
    </li>
</ol>
```

Define `formatDateTime(value: string)` with `new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))`. `Show.vue` composes `PublicWorkspaceLayout`, passes `selectedRequestId="request.id"` to the sidebar history, and renders `CustomerRequestDetail` centrally. Do not render `PublicIntakeFlow`, replies, cancellation, estimates, documents, or repair-order data.

- [ ] **Step 4: Run focused frontend validation**

```bash
npx prettier --write resources/js/components/public-portal resources/js/pages/CustomerPortal/Show.vue
npm test -- resources/js/components/public-portal/CustomerRequestDetail.test.ts resources/js/components/public-portal/CustomerRequestHistory.test.ts resources/js/pages/CustomerPortal.test.ts
npx eslint resources/js/components/public-portal resources/js/pages/CustomerPortal/Show.vue
```

Expected: tests PASS and ESLint exits 0.

- [ ] **Step 5: Commit detail UI**

```bash
git add resources/js/components/public-portal resources/js/pages/CustomerPortal/Show.vue
git commit -m "feat(portal): render request detail"
```

### Task 7: Milestone 2 verification and final checkpoint

**Files:**
- Modify only if focused verification identifies a scoped defect.

**Interfaces:**
- Consumes: completed history tasks.
- Produces: evidence that OTP-scoped history is complete.

- [ ] **Step 1: Run focused backend tests**

```bash
php artisan test tests/Feature/CustomerPortalAccessTest.php tests/Feature/CustomerPortalRequestHistoryTest.php tests/Feature/PublicIntakeSubmissionTest.php
```

Expected: all tests PASS.

- [ ] **Step 2: Run focused frontend tests**

```bash
npm test -- resources/js/layouts/PublicWorkspaceLayout.test.ts resources/js/pages/Welcome.test.ts resources/js/pages/CustomerPortal.test.ts resources/js/components/public-portal/CustomerRequestHistory.test.ts resources/js/components/public-portal/CustomerRequestDetail.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: all tests PASS.

- [ ] **Step 3: Run static validation**

```bash
vendor/bin/pint --test app/Domain/CustomerPortal/Queries app/Http/Controllers/CustomerPortalController.php routes/web.php tests/Feature/CustomerPortalRequestHistoryTest.php
npx prettier --check resources/js/layouts/PublicWorkspaceLayout.vue resources/js/pages/CustomerPortal resources/js/components/public-portal
npx eslint resources/js/layouts/PublicWorkspaceLayout.vue resources/js/pages/CustomerPortal resources/js/components/public-portal
git diff --check
```

Expected: every command exits 0.

- [ ] **Step 4: Perform security-focused self-review**

Confirm that no history reaches access/verification pages, controllers never read a history phone from request input, both queries use the verified session phone, detail scoping happens before `firstOrFail()`, foreign identifiers return 404, and payloads omit phone numbers/internal order data.

- [ ] **Step 5: Perform desktop and mobile visual checks**

Verify OTP access, empty history, one request, more than 10 requests, selected detail, long titles, missing vehicle data, expired session, desktop sidebar, and mobile menu. Do not start services or Docker without user approval.

- [ ] **Step 6: Report completion and stop**

Summarize files changed, validation results, visual evidence, and one unresolved issue if present. Do not continue into live messaging, cancellation, event history, estimates, or other deferred work.
