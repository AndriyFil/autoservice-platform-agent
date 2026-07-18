# Public Intake Bottom-Aligned Chat Design

## Goal

Make the public intake conversation use the available page height after the first submitted message, with the newest conversation content and active response controls positioned near the bottom like a modern chat interface.

## Scope

- Keep the initial intake starter centered and unchanged.
- After the first valid message, expand the intake workspace to the available viewport height.
- Let the transcript consume the flexible space, align short conversations to its bottom, and scroll internally when content grows.
- Keep the active response controls docked below the transcript.
- Remove the wide white background and top border behind the response controls.
- Preserve the individual input and button surfaces, borders, focus styles, validation, responsive stacking, editing, and submission behavior.
- Do not add image attachments in this change. Image upload and persistence will be designed separately later.

## Component Design

`Welcome.vue` will track the existing `expanded-change` event from `PublicIntakeFlow`. The page wrapper will use its current centered layout for the starter and switch to an available-height, non-centered layout for an expanded conversation. On mobile, the available height accounts for the public navigation header; on desktop, the conversation can use the full viewport height beside the sidebar.

`PublicIntakeFlow.vue` will make its section and form fill the expanded parent height. The fixed `24rem` transcript cap will be removed. The transcript remains the only scrolling conversation region, using the existing bottom-aligned inner flex container and existing scroll-to-end behavior.

The active-response wrapper will remain a non-scrolling, shrinking-disabled footer so controls stay below the transcript. Its white background and separating top border will be removed. Padding remains to provide usable spacing without creating a visible panel.

## State and Data Flow

The existing `chatExpanded` computed state remains the source of truth. Its existing watcher emits `expanded-change` when the first valid problem response advances the conversation and when cancellation resets the draft. No new global state, store, route, persistence, or business rule is introduced.

## Responsive and Accessibility Behavior

- The starter remains centered at all supported sizes.
- Expanded mobile layout fits below the existing 4rem mobile header.
- Expanded desktop layout fills the main workspace beside the sidebar.
- Existing focus movement, live-region announcements, reduced-motion handling, keyboard behavior, and internal transcript scrolling are unchanged.
- Inputs and actions retain visible boundaries and focus indicators after their parent panel becomes transparent.

## Testing

Focused component/source tests will verify:

- the Welcome page changes wrapper layout in response to expansion;
- the flow fills the expanded workspace instead of capping the transcript at `24rem`;
- the transcript remains internally scrollable and bottom-aligned;
- the active-response wrapper no longer has a white background or top border;
- the existing first-message expansion, cancellation reset, focus, and scroll-to-end behavior continue to pass.

## Non-Goals

- Image attachments, file validation, upload storage, or staff image display.
- Changes to intake fields, conversation steps, backend submission, or workshop selection.
- A broader redesign of the public workspace, sidebar, controls, or message bubbles.
