# PMMS Notification and Recipient-Resolution Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/notification-architecture.md](../01-architecture/notification-architecture.md) · [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md)

This document extends the existing Phase 0.4 [notification-architecture.md](../01-architecture/notification-architecture.md) (BC-31's runtime shape) with the full Phase 0.11 lifecycle, recipient-resolution, and governance detail. **No notification template for production use, provider integration, or notification code is created here.**

---

## 1. Notification Lifecycle

```text
Notification Intent
→ Recipient Resolution
→ Classification and Privacy Filter
→ Template Selection
→ Channel Selection
→ Queue
→ Provider Delivery
→ Delivery Status
→ Retry or Failure
→ Acknowledgment Where Applicable
→ Audit or Operational Record
```

**Notification intent originates from application workflows** — restated from [../01-architecture/notification-architecture.md, Section 1](../01-architecture/notification-architecture.md#1-principles); BC-31 decides *how* to deliver, never *whether* the underlying business fact occurred.

## 2. Recipient Resolution

Recipients may be resolved by: named user · role · active assignment · committee · delegation · sport · venue · organization · meet · workflow-task ownership · escalation owner · subscription preference.

**Recipients must be resolved at dispatch time, not at notification-intent time** — restated as this section's governing rule, since authority and assignments can change between when a notification is triggered and when it is actually sent (e.g., a committee reassignment mid-queue-delay must not deliver a task notice to the now-former assignee).

## 3. Recipient Eligibility

A resolved recipient must additionally pass an eligibility check before delivery: an active account, an unrevoked assignment matching the notification's scope, and — for anything beyond a mandatory notice (Section 6) — the recipient's own notification preferences (Section 7).

## 4. Notification Preferences

Preferences may narrow delivery channel and frequency for non-mandatory notification classes — restated from [../01-architecture/notification-architecture.md, Section 5](../01-architecture/notification-architecture.md#5-open-questions), where preference granularity (per-category opt-out versus all-or-nothing) remains an open question. No specific granularity model is finalized here.

## 5. Notification Classes

Informational · Action required · Reminder · Deadline · Escalation · Security · Privacy · Emergency · Publication · System incident · Delivery failure.

## 6. Mandatory Notifications

**User preferences must not suppress:** security alerts · account changes · privilege changes · credential revocation · emergency notifications · privacy or security incident notices where approved · required workflow-assignment notices · system-critical announcements.

Restated absolutely from [../01-architecture/notification-architecture.md, Section 1](../01-architecture/notification-architecture.md#1-principles) ("user preferences must not suppress mandatory security notices"), extended here to the full mandatory-notice list.

## 7. Notification Content Rules

Notifications must: identify context · state the required action · include safe links (never an embedded credential or one-time bypass token in plain text) · avoid excessive sensitive data · avoid medical or eligibility evidence · avoid credentials or secrets · indicate deadline where an approved deadline exists (never a fabricated one) · indicate source · support localization readiness · remain accessible, per [../06-design/accessibility-architecture.md](../06-design/accessibility-architecture.md).

## 8. Notification Classification and Privacy

Every notification inherits the classification of the underlying business fact it communicates, restated from [event-metadata-versioning-ordering-and-correlation.md, Section 6](event-metadata-versioning-ordering-and-correlation.md#6-event-privacy-and-classification) — a notification about a Restricted-tier eligibility decision is never delivered via an unencrypted, low-assurance channel with the underlying evidence attached.

## 9. Notification Delivery Status

Pending · queued · sent · delivered (where the provider confirms) · failed · retrying · permanently failed · suppressed · acknowledged (where applicable).

**Notification delivery must not be treated as proof that a user read or understood a decision** — restated absolutely per working rule 45. "Delivered" reflects transport success only; it never substitutes for the underlying decision's own durable record (Section 11).

## 10. Notification Retries and Deduplication

Retries follow the same idempotent-retry discipline as [outbox-inbox-idempotency-and-message-reliability.md, Section 4](outbox-inbox-idempotency-and-message-reliability.md#4-idempotency) — a retried notification dispatch must not produce a duplicate message to the recipient. Prevent: duplicate reminders · repeated incident alerts · notification storms · one event generating multiple equivalent notices · real-time and push duplication without purpose.

## 11. Notification Batching, Digesting, and Throttling

Support: batching · digest · priority · quiet hours where appropriate (never applied to a mandatory notice) · emergency override · user preferences (Section 4). Example named in [../01-architecture/notification-architecture.md, Section 1](../01-architecture/notification-architecture.md#1-principles): `MeetActivated` delivered to every delegation requires throttling, not a simultaneous mass-dispatch.

## 12. Notification Escalation

An unacknowledged, action-required notification may itself trigger an escalation timer (per [scheduler-calendar-deadline-and-escalation-architecture.md, Section 10](scheduler-calendar-deadline-and-escalation-architecture.md#10-escalation-timers)) — escalation notifies an escalation owner, it does not resend the original notice indefinitely.

## 13. Notification Acknowledgment

Acknowledgment (a recipient explicitly confirming receipt) is required for a defined subset of notifications (e.g., mandatory security notices) and is itself an auditable action, distinct from mere delivery-status "delivered."

## 14. Relationship to High-Integrity Domains

Restated absolutely from [../01-architecture/notification-architecture.md, Section 4](../01-architecture/notification-architecture.md#4-relationship-to-high-integrity-domains): notification delivery is never itself a high-integrity action — it carries no independent authority, and its failure never blocks or reverses the underlying decision. The underlying decision (eligibility approval, result certification, protest outcome, medal-tally publication, credential revocation) is durably recorded by its owning context *before* notification dispatch, per [../01-architecture/laravel-architecture.md, Section 4 "Transaction Boundaries"](../01-architecture/laravel-architecture.md#4-transaction-boundaries).

## 15. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-13 (notification-preference granularity) and WD-14 (digest/summary mode — "no evidenced need yet," restated from Phase 0.4).
