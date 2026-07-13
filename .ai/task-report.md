# Task Report

## Goal

Add a clear but deliberately low-emphasis Customer Portal preview entry to the public homepage without displacing the workshop-focused SaaS hero or promising request-history functionality that is not implemented yet.

## Files Changed

- `resources/js/pages/Welcome.vue`
- `resources/js/pages/Welcome.test.ts`
- `.ai/task-report.md`

## Implementation Summary

- Added an always-visible `My requests` link to the primary homepage navigation.
- Linked customer access to `customer-portal.index`, allowing verified customers through and unverified customers to follow the existing access redirect.
- Kept `Create workshop account` and `Staff login` as the primary workshop-facing hero actions.
- Replaced the ambiguous customer sentence with explicit new-request guidance:
  - `Need to send a new request? Use the workshop-specific link provided by your workshop.`
- Added a subtle `Customer access preview` panel beneath the workshop actions.
- Explained that access uses phone verification with no account or password.
- Explicitly disclosed that request history is not available yet.
- Added a `Verify phone access` CTA without presenting the preview as a finished request portal.
- Made the header/navigation wrap on narrow screens.
- Kept the global customer link inside an always-rendered, labelled navigation landmark.
- Connected the preview `aside` to an accessible `h2` heading.

## Architecture Decisions

- This is a presentation-only change. It reuses the existing Customer Portal route and middleware instead of adding backend props, controllers, stores, or duplicated access logic.
- The workshop acquisition hero remains visually and structurally primary because the root page is still the AutoService SaaS marketing surface.
- New request intake and returning customer access remain distinct: new requests use workshop-specific links; returning access uses the global verified-phone portal.
- The preview copy is intentionally honest about the current placeholder scope.

## Tradeoffs

- Customer Portal access is visible before request history exists, so the panel is styled and worded as a preview rather than a primary product promise.
- The page test is a source-level regression test, consistent with the existing Welcome page test style. It verifies copy, route references, and semantic markers but does not mount the Vue component.
- Staff/account links remain repeated in the header and hero because they serve different navigation and conversion contexts; this task did not redesign the broader marketing page.

## Tests

TDD evidence:

- Initial targeted run: expected RED with `3` failures and `1` passing test because the customer link, preview, and new-request distinction did not exist.
- Semantic-review run: expected RED with `2` failures and `2` passing tests because the customer link was outside the navigation landmark and the preview lacked an associated heading.

Final commands:

- `npm run test -- resources/js/pages/Welcome.test.ts`
  - Passed: `1` file, `4` tests.
- `npm run build`
  - Passed: Vite production build completed successfully with `2081` modules transformed.
  - Non-blocking warning: installed `caniuse-lite` browser data is stale.
- `git diff --check -- resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts`
  - Passed with no whitespace errors.

## Risks

- The preview does not provide request history; the limitation is stated directly in the UI.
- Existing unrelated worktree changes were preserved.

## Follow Ups

- When customer request listing is implemented, replace preview wording with the final `View my requests` product language and reconsider the panel's visual priority.
- A mounted Vue interaction test can be added later if the homepage navigation gains client-side state or behavior.
