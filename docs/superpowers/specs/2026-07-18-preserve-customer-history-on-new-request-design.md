# Preserve Customer History on New Request Design

## Goal

Keep a verified customer's recent request history visible in the public workspace sidebar when they navigate from the customer portal to the New Request homepage.

## Scope

- Validate the existing short-lived verified-phone session on the homepage.
- Query recent booking requests only for the active verified phone.
- Pass the same safe request-summary payload used by the customer portal.
- Render `CustomerRequestHistory` in the Welcome page sidebar when recent requests exist.
- Keep the standard `My requests` link for unverified visitors and verified customers with no history.
- Do not expose the verified phone or add browser-side history caching.

## Backend Design

`PublicIntakeController::create` will receive the current request and `CustomerRequestIndexQuery`. It will treat the session as verified only when `customer_portal.verified_phone` is a non-empty string, `customer_portal.verified_until` is an integer timestamp, and the timestamp is still in the future. For a valid session it will query history by the verified phone and provide `recentRequests` and `hasMoreRequests` to `Welcome`. For missing or expired verification it will provide no history props and perform no customer request query.

The existing `CustomerRequestIndexQuery` remains the single source for phone-scoped filtering and safe presentation mapping. No phone number is included in the Inertia payload.

## Frontend Design

`Welcome.vue` will accept optional `recentRequests` and `hasMoreRequests` props. When `recentRequests` contains at least one item, it will fill `PublicWorkspaceLayout`'s `history` slot with the existing `CustomerRequestHistory` component. When history is absent or empty, it will omit the slot so `PublicWorkspaceLayout` keeps its existing `My requests` link.

The intake conversation and request submission behavior remain unchanged.

## Testing

- A feature test will verify that an active verified session receives only that phone's recent request summaries on `/` and never receives the phone value.
- A feature test will verify that an expired session receives no history props.
- A frontend test will verify that Welcome uses `CustomerRequestHistory` through the workspace history slot and retains the fallback when history is empty.
- Existing public intake and customer portal tests will remain green.

## Non-Goals

- Changing verification duration or authentication rules.
- Creating customer user accounts.
- Persisting portal history in browser storage.
- Changing request detail pages, intake fields, or request creation behavior.
