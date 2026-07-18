# PMMS Email, SMS, Push, and In-App Delivery Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [notification-and-recipient-resolution-architecture.md](notification-and-recipient-resolution-architecture.md) · [../01-architecture/notification-architecture.md, Section 2](../01-architecture/notification-architecture.md#2-delivery-channel-categories)

**No SMS, email, or push-provider integration is created here.**

---

## 1. Delivery Channels

Restated from [../01-architecture/notification-architecture.md, Section 2](../01-architecture/notification-architecture.md#2-delivery-channel-categories):

| Channel | Use | Reliability Expectation |
|---|---|---|
| In-App | Default for most operational notices, visible on next login/visit | Durable, persisted |
| Email | Formal notices, registration/eligibility status changes, reports | Queued (`notifications` category), retried |
| SMS | Time-sensitive advisories where approved — **deferred integration**, per [../00-product/product-scope.md, Section 8 "Deferred Integrations"](../00-product/product-scope.md#8-deferred-integrations) and [OD-25](../00-product/open-decisions.md#od-25--integration-requirements) | Queued, retried, provider-dependent |
| Push | Mobile-app alerts for field staff (schedule change, assignment update) | Queued, best-effort |
| Real-time alert | Immediate operational awareness (committee alerts, security alerts) | Best-effort, non-durable — paired with a durable in-app notification for anything consequential |

**No channel should be the only durable record of an official decision** — restated absolutely; the owning context's own durable state (per [notification-and-recipient-resolution-architecture.md, Section 14](notification-and-recipient-resolution-architecture.md#14-relationship-to-high-integrity-domains)) is always the record of truth, with every channel above a courtesy delivery layered on top.

## 2. In-App Notifications

The primary detailed notification channel — persisted, queryable, and the fallback destination for any notification whose delivery on another channel fails or is suppressed by preference. In-app notifications support the full content-rule set (Section, [notification-and-recipient-resolution-architecture.md, Section 7](notification-and-recipient-resolution-architecture.md#7-notification-content-rules)) without the length/format constraints SMS imposes.

## 3. Email Notifications

Used for formal, less time-sensitive notices. Queued via the `notifications` queue category (per [../01-architecture/event-and-queue-architecture.md, Section 1](../01-architecture/event-and-queue-architecture.md#1-queue-categories)), retried with backoff on transient provider failure. No specific mail-provider vendor is selected in this phase — `.env.example`'s existing `MAIL_MAILER=log` placeholder remains the confirmed baseline pending a vendor decision.

## 4. SMS Notifications

**Explicitly deferred** — no SMS gateway is selected, integrated, or assumed. Where an SMS notice is described elsewhere in this package (e.g., a time-sensitive advisory), it is described as a future channel option, never as an available delivery mechanism today. Tracked as an open integration requirement in [OD-25](../00-product/open-decisions.md#od-25--integration-requirements).

## 5. Push Notifications

Used for mobile-app operational alerts (schedule change, assignment update) once the Flutter mobile application exists — `mobile/` does not yet exist in this repository, restated from every prior phase's identical finding. No push-provider vendor is selected.

## 6. Real-Time Alerts (Cross-Reference)

Full detail: [realtime-broadcast-and-reverb-message-architecture.md](realtime-broadcast-and-reverb-message-architecture.md). A real-time alert is transient and non-durable — it is never the sole record of a consequential notice; anything consequential is paired with a durable in-app (and, where appropriate, email) notification.

## 7. Channel Selection Logic

Channel selection is driven by the notification's class (per [notification-and-recipient-resolution-architecture.md, Section 5](notification-and-recipient-resolution-architecture.md#5-notification-classes)) and the recipient's resolved preferences (Section 4 of that document) — a mandatory notice always includes in-app delivery regardless of preference, with email as a durable secondary channel for anything security- or privilege-related.

## 8. Delivery-Channel Failure Handling

A channel-specific delivery failure (e.g., an email bounce) does not silently disappear — it is recorded (Section, [notification-and-recipient-resolution-architecture.md, Section 9](notification-and-recipient-resolution-architecture.md#9-notification-delivery-status)) and, for a mandatory notice, triggers fallback to the in-app channel at minimum.

## 9. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-15 (mail-provider vendor selection) and WD-16 (SMS/push provider selection and integration timing, tracked jointly with OD-25).
