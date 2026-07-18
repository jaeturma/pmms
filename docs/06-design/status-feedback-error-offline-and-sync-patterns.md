# PMMS Status, Feedback, Error, Offline, and Sync Patterns

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md) · [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md) · [accessibility-architecture.md](accessibility-architecture.md)

This document defines status vocabulary, feedback components, error states, and offline/sync/real-time interface patterns. **No component is created here.**

---

## 1. Status Vocabulary and Status Badges

Every status display includes: plain-language label · semantic tone (per [color-theme-and-surface-system.md, Section 1](color-theme-and-surface-system.md#1-color-architecture)) · icon where useful · accessible description · allowed actions (what this status permits next) · timestamp where material · actor where material · reason where material.

**High-integrity states are visually distinct** — restated absolutely; a Draft, Provisional, Validated, Certified, Held, Superseded, or Published record never share the same visual treatment, extending working rule 33 into the component layer.

## 2. Alerts, Notifications, Dialogs, and Drawers

| Component | Purpose |
|---|---|
| Alerts | Inline, page-level or section-level messages (building on the existing `alert.tsx`, `alert-error.tsx`) |
| Notifications | Per [content-design-terminology-help-and-onboarding.md, Section 6](content-design-terminology-help-and-onboarding.md#6-notification-experience) (building on the existing `sonner.tsx` toast component) |
| Dialogs | Focused, blocking interactions (building on the existing `dialog.tsx`) — reserved for genuinely blocking decisions, never used for routine information display |
| Drawers | Non-blocking, contextual panels (building on the existing `sheet.tsx`) — used for detail views and secondary workflows that don't require full attention capture |

## 3. Empty States

Explain: what the area contains · why it may be empty · whether the user has permission · whether filters are hiding results · whether setup is incomplete · what action is available.

### Differentiated Empty-State Categories

No records exist · no matching results (filtered) · no permission · not configured · data not synchronized · data not published — restated absolutely, each is a **distinct** empty state, never collapsed into one generic "nothing here" message, since the correct next action differs for each.

## 4. Loading States and Skeletons

Initial page loading · partial loading · table loading · action processing · upload progress · export generation · import processing · real-time reconnect · sync progress · background recalculation.

### Rules

Preserve layout where possible (using skeletons, building on the existing `skeleton.tsx`/`spinner.tsx` components, rather than a layout-shifting blank state) · avoid indefinite spinners without explanation · show progress when measurable · allow safe cancellation where appropriate · **do not imply success before server confirmation** — restated absolutely, an optimistic-UI update is never presented as final until the server actually confirms it, directly protecting the "no data corruption from failed operations" principle.

## 5. Error States

| Category | Recovery Path |
|---|---|
| Validation | Correct the flagged field(s) and resubmit |
| Permission | Explain the restriction; offer a path to request access where applicable |
| Authentication | Re-authenticate |
| Conflict | Route to human review, per Section 7 |
| Stale data | Refresh and re-attempt |
| Offline | Per Section 6 |
| Sync rejected | Per Section 6 |
| Service unavailable | Retry guidance, status-page reference where available |
| Upload failed | Retry, with the original file preserved for re-attempt |
| Export failed | Retry, with the export request preserved |
| Real-time disconnected | Automatic reconnection attempt with visible status, falling back to polling |
| Unexpected error | A generic, honest message directing to support — never a raw stack trace or internal exception |

**Every error has an appropriate recovery path** — restated absolutely; an error state with no next action is an incomplete design.

## 6. Offline and Sync Experience

Restated and consolidated from [flutter-mobile-experience-architecture.md, Section 2](flutter-mobile-experience-architecture.md#2-offline-mobile-experience) for cross-platform (React and Flutter) application — the same state vocabulary (Online, Offline, Limited connectivity, Pending sync, Synchronizing, Accepted, Rejected, Conflict, Superseded, Device revoked, Authorization expired) applies wherever connectivity state is relevant on either platform.

### Rules

Do not show a local draft as officially accepted · preserve unsynced work · explain conflicts clearly · separate retryable from non-retryable failures · show last successful sync · **the server is always the final authority** — restated absolutely · allow safe manual review before an ambiguous conflict resolves.

## 7. Conflict States

A sync or concurrent-edit conflict is presented with: both versions in comparison · which is server-authoritative · what the user's options are (accept server version, resubmit, escalate) · and, for any high-integrity domain, a route to human review rather than automatic resolution — restated absolutely from [../02-data/offline-sync-and-conflict-data-model.md, Section 3](../02-data/offline-sync-and-conflict-data-model.md#3-conflict-resolution-data).

## 8. Real-Time Experience (Cross-Reference)

Live indicators, freshness, reconnection state, and update highlighting are detailed in [dashboard-table-chart-and-data-visualization-standards.md, Section 3](dashboard-table-chart-and-data-visualization-standards.md#3-real-time-data-display) — not duplicated here.

## 9. Success and Destructive-Action Confirmation

**Success confirmation** is immediate, specific (states what happened, not merely "Success"), and — for consequential actions — includes the resulting state and next steps. **Destructive-action confirmation** requires deliberate acknowledgment (never a single accidental click), states the specific consequence in plain language, and is visually and spatially distinguished from routine confirmations, restated from [responsive-touch-keyboard-and-device-behavior.md, Section 4](responsive-touch-keyboard-and-device-behavior.md#4-touch-interaction)'s spacing rule.

## 10. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably the specific "stale" threshold before a real-time indicator flags data as outdated, and notification-center retention/archival behavior.
