# PMMS Device and Service Identity Model

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [identity-model.md](identity-model.md) · [offline-authorization-model.md](offline-authorization-model.md)

Devices and services are **never** substitutes for a User Account. A device is operated by a User Account during a session; a service acts on behalf of the platform itself. Both carry their own distinct, revocable, auditable identity. **No device table or credential schema is designed here** — this is a conceptual model.

---

## 1. Device Identity Categories

| Device Category | Operational Purpose | Typical Assignment |
|---|---|---|
| QR scanner stations | Access validation at gates, meal lines, billeting, transport | Venue + Device + Shift (see [role-catalog.md, ROLE-48](role-catalog.md#role-48--access-control-operator)) |
| Result encoding stations | Score capture at competition venues | Meet + Sport + Venue |
| Scoreboard devices | Public/venue display of live scores | Venue (read-only, no write authority) |
| Kiosks | Self-service information display | Venue (read-only) |
| Mobile official devices | Technical Official field devices for scoring/officiating | Meet + Sport + Event (bound to the officiating assignment) |
| Accreditation printers | Physical credential printing | Committee (Accreditation) + Venue |
| Gate devices | Physical access control hardware paired with a scanner | Venue + Device |
| Offline venue servers | Local synchronization/caching point for a venue during connectivity loss | Venue |

## 2. Per-Device Identity Fields (Conceptual)

| Field | Meaning |
|---|---|
| Device registration | The act of formally enrolling the device in PMMS |
| Device name | Human-readable identifier (e.g., "Gate 1 Scanner") |
| Device type | One of the categories above |
| Device owner | The committee/role responsible for the device (typically ICT Coordinator, ROLE-44) |
| Organization | The organization the device is provisioned under |
| Meet assignment | Which meet the device is currently active for |
| Venue assignment | Which venue the device is bound to |
| Operational purpose | What the device is authorized to do (scan, score, display) — never broader than needed |
| Trust status | Trusted / Untrusted / Under Review / Revoked |
| Credential status | Active / Expired / Revoked |
| Activation | When the device credential became usable |
| Revocation | When and why the device credential was disabled |
| Last seen | Most recent successful check-in/sync |
| Software version | Client version running on the device, relevant for offline-cache compatibility |
| Offline capability | Whether this device type is expected to operate disconnected (per [offline-authorization-model.md](offline-authorization-model.md)) |
| Audit behavior | What this device logs locally and how it syncs those logs |

## 3. Device Trust Principles

- **Least privilege:** A scanner device is trusted only to perform `access-scan.validate` — it is never trusted with `accreditation-credential.issue` or any write authority over the credentials it validates against.
- **Device ≠ operator:** Device trust and operator (User Account) authentication are **combined, independent inputs** to the authorization decision (see [authorization-decision-model.md, Step 12](authorization-decision-model.md#2-decision-sequence)) — a trusted device with an unauthenticated/unauthorized operator still fails the decision, and vice versa.
- **Shared-device risk:** Devices used by rotating staff (e.g., a shared scanner across shifts) require the operator's own authentication at the start of each shift/session — the device's trust status alone never substitutes for operator identity (named anti-pattern: "shared scanner credentials," Section 38 of the main document).
- **Revocation independence:** A device can be revoked (e.g., lost/stolen) without affecting the User Accounts who previously operated it, and a User Account can be revoked without affecting other devices that account never compromised.

## 4. Device Loss

A lost or stolen device should trigger:
1. Immediate device-credential revocation (server-side, propagating to any offline-cache-dependent context as a high-priority sync item — see [offline-authorization-model.md](offline-authorization-model.md)).
2. A security review of any actions performed on that device since its last confirmed-trusted check-in.
3. Re-provisioning of a replacement device, if needed, as a **new** device identity — never a reissued credential on the assumption the physical device will be recovered.

This directly addresses RSK-07 (lost or stolen mobile devices) from [Phase 0.1](../00-product/assumptions-constraints-risks.md).

---

## 5. Service Identity Categories

| Service Category | Purpose |
|---|---|
| Queue worker identity | Background job processing (Laravel Horizon workers) |
| Scheduled job identity | Time-triggered automated tasks (e.g., meet-readiness recalculation) |
| Integration client | External/adjacent-system integration (deferred per [Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations)) |
| Public API client | External consumer of any future public API |
| Mobile synchronization client | The server-side counterpart handling offline-device sync |
| Reporting service | BC-33 Reporting and Analytics' data-access identity |
| AI service | Any AI-assisted feature's execution identity (see Section 6 below and [phase-0.3, Section 29](phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary)) |
| Notification service | BC-31 Notifications' delivery-channel identity |
| Import process | Bulk data import (e.g., historical delegation rosters) |
| Export process | Bulk data export (reports, audit exports) |

## 6. Service Identity Principles

Per working rules, service identities must:

- **Have narrowly scoped credentials** — a queue worker processing score-validation jobs should not hold credentials broad enough to also modify user accounts.
- **Use non-human identity records** — never a shared "system" login masquerading as a human account.
- **Avoid shared administrator credentials** — no service uses a Platform Administrator's personal credential.
- **Be revocable** — independently of any human account.
- **Be auditable** — every service action is attributed to its specific service identity, never anonymized as "system."
- **Have expiry or rotation policies** — service credentials are not permanent, unexpiring secrets (specific rotation cadence is a later-phase security-policy decision — see [access-open-decisions.md](access-open-decisions.md)).
- **Never silently act as a human approver** — a scheduled job that, say, recalculates a read-model dashboard is fine; a scheduled job that would auto-approve an eligibility case is a direct violation of [high-integrity-domain-rules.md](high-integrity-domain-rules.md) and must not exist.

## 7. AI-Service Identity (Cross-Reference)

The AI Assistant Execution Identity (per [identity-model.md](identity-model.md#identity-categories)) is a specialization of Service Identity with an additional, stricter constraint: **it must always be bound to either the requesting User Account's own authority or an approved Service Identity's narrow scope — it never carries independent standing authority of its own.** This means an AI feature's effective permissions are the *intersection* of what the AI service is technically capable of and what the requesting user is authorized to see/do, never a union that could exceed the user's own access. See [phase-0.3-access-and-assignment-architecture.md, Section 29](phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary) for the full policy, and [domain-open-decisions.md, DD-26](domain-open-decisions.md#dd-26--ai-service-data-access-boundaries-domain-specific-framing) for the data-boundary question this depends on.

## 8. Service Compromise

If a service identity is suspected compromised:
1. Immediate credential revocation/rotation for that specific service identity only — not a platform-wide credential reset unless the compromise is shown to be systemic.
2. Review of all actions attributed to that service identity since the last known-good state.
3. Where the compromised service had write access to any high-integrity domain, an elevated review of every write it performed during the suspected compromise window (per [high-integrity-domain-rules.md](high-integrity-domain-rules.md) evidence-retention principles).

## 9. Open Questions

- Specific credential-rotation cadence for service identities (Section 6).
- Whether a dedicated device-management committee role is warranted beyond ICT Coordinator (ROLE-44), given the scale of scanner/encoder devices expected at a multi-venue meet.
- Public API client onboarding process, once any external integration becomes concrete (deferred per Phase 0.1 scope).

Tracked in [access-open-decisions.md](access-open-decisions.md).
