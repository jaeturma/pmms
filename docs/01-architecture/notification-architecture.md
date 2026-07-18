# PMMS Notification Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [bounded-context-catalog.md, BC-31](bounded-context-catalog.md#bc-31--notifications) · [event-and-queue-architecture.md](event-and-queue-architecture.md) · [role-catalog.md](role-catalog.md)

This document defines the runtime shape of Notifications (BC-31). **No notification channel, template, or Laravel Notification class is created here.**

---

## 1. Principles

- **Notification intent originates from application workflows** — a domain event (e.g., `EligibilityRequirementsSubmitted`) or an Application-layer use case decides *that* a notification is warranted; BC-31 only decides *how* to deliver it.
- **Notification context is separated from delivery transport** — the business reason for a notification (an Application-layer concern) is distinct from the channel adapter that delivers it (an Infrastructure-layer concern), per [laravel-architecture.md](laravel-architecture.md)'s layering discipline.
- **Recipient resolution respects role, assignment, scope, and preferences** — a notification about an eligibility case reaching Delegation A goes to that delegation's Delegation Head/Coach (per their Assignment scope, [assignment-model.md](assignment-model.md)), never a blanket broadcast to every user with the Coach role platform-wide.
- **Channels** may include in-app, email, SMS, push, and real-time alerts (the last via Reverb, per [realtime-architecture.md](realtime-architecture.md) — though a Reverb broadcast and a durable Notification are distinct concepts, per [laravel-architecture.md, Section 5](laravel-architecture.md#5-domain-event-architecture)).
- **Notifications must not become the only record of a business decision** — an `EligibilityApproved` decision is durably recorded by Eligibility and Clearance (BC-09) itself; the notification is a courtesy delivery of that fact, never the fact's sole record. If a notification fails to deliver, the underlying decision is unaffected.
- **Sensitive content is minimized** — a notification says "your eligibility case has been reviewed — view details in PMMS," not a message embedding the case's restricted evidence content.
- **Delivery retries are idempotent** — a retried notification does not become five copies of the same email.
- **Templates are versioned** — a template change does not retroactively alter what a recipient sees for an already-delivered notification (for audit/support purposes, a "what was actually sent" record is preserved, not just "what template exists today").
- **Failed delivery is observable** — per the `notifications` queue category in [event-and-queue-architecture.md](event-and-queue-architecture.md).
- **Bulk notifications require throttling** — e.g., a `MeetActivated` notification reaching every registered delegation is paced, not fired as thousands of simultaneous synchronous sends.
- **Emergency notifications may use elevated routing** — a security/medical emergency advisory bypasses normal batching/throttling and quiet-hours preferences (next bullet's exception).
- **User preferences must not suppress mandatory security notices** — a user cannot opt out of, e.g., a credential-revocation notice about their own account.
- **Public announcements are not the same as private notifications** — a Media and Communications (BC-28) public announcement flows through Public Information (BC-29)'s publication path, not through BC-31's per-recipient delivery mechanism, even though both may be triggered by related events.

## 2. Delivery Channel Categories

| Channel | Use | Reliability Expectation |
|---|---|---|
| In-app | Default for most operational notices (visible on next login/visit) | Durable — persisted, not lost if unread |
| Email | Formal notices, registration/eligibility status changes, reports | Queued (`notifications` category), retried |
| SMS | Time-sensitive advisories where approved (deferred integration, per [Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations)) | Queued, retried, provider-dependent |
| Push | Mobile-app alerts for field staff (e.g., schedule change, assignment update) | Queued, best-effort (mobile push delivery is inherently best-effort) |
| Real-time alert | Immediate operational awareness (committee alerts, security alerts) | Best-effort, non-durable (per [realtime-architecture.md](realtime-architecture.md)) — paired with a durable in-app notification for anything consequential |

## 3. Recipient Resolution

Recipient resolution consumes the same scope/assignment model as any other authorization-aware operation (per [scope-model.md](scope-model.md) and [assignment-model.md](assignment-model.md)) — e.g., resolving "who should be notified of a Venue Schedule change" queries active Venue-scoped and affected-Sport-scoped assignments, not a hardcoded distribution list.

## 4. Relationship to High-Integrity Domains

Notifications about high-integrity events (eligibility decisions, result certification, protest outcomes, medal tally publication, credential revocation) are triggered by their owning context's domain event (per [domain-events-catalog.md](domain-events-catalog.md)) but the notification delivery itself is never a high-integrity action — it carries no independent authority and its failure never blocks or reverses the underlying decision. This is a deliberate design choice: notification delivery is asynchronous and best-effort-plus-retry, while the decision it announces is already durably committed before the notification is even queued (per [laravel-architecture.md, Section 4](laravel-architecture.md#4-transaction-boundaries), "external side effects occur after safe state persistence").

## 5. Open Questions

- SMS/push provider selection — deferred, no vendor selected (per working rule against inventing integration requirements).
- Notification-preference granularity (per-category opt-out vs. all-or-nothing) — implementation-phase UX decision.
- Whether a digest/summary mode (vs. per-event notification) is needed for high-volume roles (e.g., a Secretariat Head reviewing many registrations) — no evidenced need yet.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
