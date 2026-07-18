# Customer Portal Repair Order Status Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a verified customer the safe status summary of a repair order linked to their service request.

**Architecture:** Extend the phone-scoped `CustomerRequestShowQuery` with a nullable, explicitly mapped repair-order summary. Render that summary in `CustomerRequestDetail` and its progress timeline without introducing a public repair-order route or exposing the internal model.

**Tech Stack:** Laravel, Eloquent, Inertia, Vue 3, TypeScript, PHPUnit, Vitest, Vue Test Utils.

## Global Constraints

- Expose only repair order identifier, status, opened time, and updated time.
- Do not expose estimates, prices, line items, documents, internal notes, staff identity, or workshop-only actions.
- Keep verified-phone request scoping as the customer access boundary.
- Leave the page unchanged when no repair order is linked.

---

### Task 1: Add the customer-safe repair-order payload

**Files:**
- Modify: `tests/Feature/CustomerPortalRequestHistoryTest.php`
- Modify: `app/Domain/CustomerPortal/Queries/CustomerRequestShowQuery.php`

**Interfaces:**
- Consumes: `BookingRequest::repairOrder()` and `RepairOrderStatus::label()`.
- Produces: nullable `request.repairOrder` with `id`, `status`, `openedAt`, and `updatedAt`.

- [ ] **Step 1: Write the failing feature test**

Create an `InProgress` repair order for the owned booking request. Assert `request.repairOrder.id`, `status.value`, `status.label`, `openedAt`, and `updatedAt`. Also assert `notes` and `createdByUserId` are missing. Update the existing no-order assertion from `missing('request.repairOrder')` to `where('request.repairOrder', null)`.

```php
$repairOrder = RepairOrder::factory()->forCustomer($bookingRequest->customer)->create([
    'workshop_id' => $workshop->id,
    'booking_request_id' => $bookingRequest->id,
    'status' => RepairOrderStatus::InProgress,
    'opened_at' => now()->subHour(),
]);

->where('request.repairOrder.id', $repairOrder->id)
->where('request.repairOrder.status.value', 'in_progress')
->where('request.repairOrder.status.label', 'In progress')
->where('request.repairOrder.openedAt', $repairOrder->opened_at->toIso8601String())
->where('request.repairOrder.updatedAt', $repairOrder->updated_at->toIso8601String())
->missing('request.repairOrder.notes')
->missing('request.repairOrder.createdByUserId')
```

- [ ] **Step 2: Run the feature test and verify failure**

Run `php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php --filter='owned_request|linked_repair_order'`.

Expected: FAIL because `request.repairOrder` is not present.

- [ ] **Step 3: Map only approved fields in the query**

Eager-load the linked order:

```php
->with(['workshop:id,name', 'repairOrder:id,booking_request_id,status,opened_at,created_at,updated_at'])
```

Append the explicitly mapped nullable payload:

```php
'repairOrder' => $request->repairOrder
    ? [
        'id' => $request->repairOrder->id,
        'status' => [
            'value' => $request->repairOrder->status->value,
            'label' => $request->repairOrder->status->label(),
        ],
        'openedAt' => ($request->repairOrder->opened_at ?? $request->repairOrder->created_at)->toIso8601String(),
        'updatedAt' => $request->repairOrder->updated_at->toIso8601String(),
    ]
    : null,
```

- [ ] **Step 4: Run the feature tests and verify success**

Run the same command. Expected: PASS, including the existing 404 coverage for another verified phone.

### Task 2: Render the repair-order card and milestone

**Files:**
- Modify: `resources/js/components/public-portal/types.ts`
- Modify: `resources/js/components/public-portal/CustomerRequestDetail.test.ts`
- Modify: `resources/js/components/public-portal/CustomerRequestDetail.vue`

**Interfaces:**
- Consumes: nullable `CustomerRequestDetail.repairOrder` from Task 1.
- Produces: customer-visible order summary card and progress milestone.

- [ ] **Step 1: Add the failing component tests**

Extend the fixture with a repair order, then assert the card, status, and timeline milestone. Mount a second fixture with `repairOrder: null` and assert the card is absent.

```ts
repairOrder: {
    id: 73,
    status: { value: 'in_progress', label: 'In progress' },
    openedAt: '2026-07-18T08:30:00+03:00',
    updatedAt: '2026-07-18T10:00:00+03:00',
},
```

```ts
expect(wrapper.get('[data-testid="repair-order-summary"]').text()).toContain('Repair order #73');
expect(wrapper.get('[data-testid="repair-order-summary"]').text()).toContain('In progress');
expect(wrapper.get('ol[aria-label="Request progress"]').text()).toContain('Repair order created');
expect(mount(CustomerRequestDetail, {
    props: { request: request({ repairOrder: null }) },
}).find('[data-testid="repair-order-summary"]').exists()).toBe(false);
```

- [ ] **Step 2: Run the component test and verify failure**

Run `npm test -- resources/js/components/public-portal/CustomerRequestDetail.test.ts`.

Expected: FAIL because the contract and markup do not contain `repairOrder`.

- [ ] **Step 3: Extend the TypeScript contract**

Add this nullable field to `CustomerRequestDetail`:

```ts
repairOrder: {
    id: number;
    status: {
        value: 'draft' | 'in_progress' | 'completed' | 'cancelled';
        label: string;
    };
    openedAt: string;
    updatedAt: string;
} | null;
```

- [ ] **Step 4: Render the customer-facing summary**

Add a conditional public card between request details and progress:

```vue
<section v-if="request.repairOrder" data-testid="repair-order-summary" class="mt-6 rounded-3xl border border-[#dfe4e4] bg-white p-6 shadow-sm sm:p-8">
    <p class="public-kicker">Repair order</p>
    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-[#0b1f33]">Repair order #{{ request.repairOrder.id }}</h2>
        <span class="rounded-full bg-[#e9f3f2] px-3 py-1.5 text-sm font-semibold text-[#0e7c86]">
            {{ request.repairOrder.status.label }}
        </span>
    </div>
    <p class="mt-4 text-sm text-[#607086]">Opened {{ formatDateTime(request.repairOrder.openedAt) }}</p>
    <p class="mt-1 text-sm text-[#607086]">Last updated {{ formatDateTime(request.repairOrder.updatedAt) }}</p>
</section>
```

Append a conditional timeline item containing “Repair order created,” its current status, and `repairOrder.updatedAt`.

- [ ] **Step 5: Run focused verification**

Run:

```bash
npm test -- resources/js/components/public-portal/CustomerRequestDetail.test.ts
php artisan test tests/Feature/CustomerPortalRequestHistoryTest.php --filter='owned_request|linked_repair_order'
npx prettier --check resources/js/components/public-portal/types.ts resources/js/components/public-portal/CustomerRequestDetail.vue resources/js/components/public-portal/CustomerRequestDetail.test.ts
vendor/bin/pint --test app/Domain/CustomerPortal/Queries/CustomerRequestShowQuery.php tests/Feature/CustomerPortalRequestHistoryTest.php
git diff --check
```

Expected: all focused tests and formatting checks pass.
