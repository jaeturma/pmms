# PMMS Offline and Synchronization Boundaries

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [context-map.md](context-map.md) · [high-integrity-domain-rules.md](high-integrity-domain-rules.md) · [../00-product/operating-model.md, Section 17](../00-product/operating-model.md#17-offline-and-synchronization-principles)

This document identifies which bounded contexts have meaningful offline relevance and defines conceptual offline/synchronization boundaries for each. **No technical synchronization protocol, conflict-resolution algorithm, or storage mechanism is defined here** (per working rule 5–8) — this is a boundary and principle document for the architecture phase that follows.

## Contexts with High Offline Relevance

| Context | Offline Relevance | Primary Offline Use Case |
|---|---|---|
| [BC-20 Access Validation](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline) | **Critical** | High-volume credential scanning at venue/meal/billeting/transport access points |
| [BC-15 Scoring](bounded-context-catalog.md#bc-15--scoring-high-integrity) | **Critical** | Score/time/measurement capture at competition venues |
| [BC-13 Technical Officials](bounded-context-catalog.md#bc-13--technical-officials) | Medium | Day-of assignment reference and acceptance at venues |
| [BC-14 Venue and Schedule](bounded-context-catalog.md#bc-14--venue-and-schedule) | Medium | Schedule reference at venues with poor connectivity |
| [BC-21 Medical Operations](bounded-context-catalog.md#bc-21--medical-operations-restricted) | High | Incident logging during emergencies regardless of connectivity |
| [BC-24 Transportation](bounded-context-catalog.md#bc-24--transportation) | Medium | Dispatch/boarding tracking en route |
| [BC-22 Billeting](bounded-context-catalog.md#bc-22--billeting) | Medium | Check-in/out at accommodation facilities |
| [BC-23 Food Services](bounded-context-catalog.md#bc-23--food-services) | Medium–High | Meal distribution validation at high-throughput, often poorly connected distribution points |
| [BC-27 ICT Service Operations](bounded-context-catalog.md#bc-27--ict-service-operations) | Medium | On-site support ticket logging |
| [BC-25 Security Operations](bounded-context-catalog.md#bc-25--security-operations) | High | Incident reporting regardless of connectivity |

For each, offline boundaries are documented below.

---

## BC-20 Access Validation *(Critical Offline Priority)*

- **Offline use case:** Scanning credentials at gates, meal lines, billeting entry, and transport boarding, potentially with no connectivity for extended periods.
- **Required local data:** A pre-synced cache of credential validity (from BC-19 Accreditation), including known-revoked credentials, refreshed as frequently as connectivity allows.
- **Allowed offline actions:** Grant/deny access based on the local cache; log the scan locally.
- **Prohibited offline actions:** None of the access decision itself is prohibited offline (this is the intended design), but **credential issuance/revocation authorship** (BC-19) never happens on the scanning device — only the cached *result* of an already-issued/revoked decision is used.
- **Server authority:** BC-19 Accreditation remains the sole authority on credential validity; the device cache is always a follower, never a source.
- **Conflict types:** A credential revoked after the device's last sync but presented before the device's next sync — a stale-cache false-accept risk.
- **Conflict resolution concept:** Revocation propagation should be treated as **highest-priority sync content** — devices should pull revocation updates preferentially over other data when connectivity is available, to minimize the stale-cache window. The actual resolution policy (fail-open vs. fail-closed for ambiguous cases) is a security decision requiring input from the Security Committee/ICT stakeholders, not decided here.
- **Idempotency needs:** A scan synced twice (e.g., after a dropped connection retried) must not create duplicate access records.
- **Device identity:** Each scanning device should have a distinguishable identity so anomalous device behavior (e.g., a device generating implausible scan volume) can be flagged.
- **User identity:** The staff member operating the device should be identifiable, distinct from the device itself.
- **Time synchronization concern:** Device clocks may drift while offline; scan timestamps should be reconciled against server time on sync, with the local capture time retained as well.
- **Audit strategy:** Every scan (granted or denied) is logged locally and synced; audit trail is never lost due to offline operation.
- **Data encryption concern:** The locally cached credential-validity set contains identity-linked data and should be protected at rest on the device (mechanism deferred to architecture phase).
- **Recovery process:** A device that has been offline for an extended period should clearly surface its cache staleness to the operator before resuming full autonomous operation.
- **Synchronization priority:** Revocations > new issuances > general reference data.
- **Network degradation behavior:** Should degrade gracefully from real-time validation to cached validation to (if cache is also stale/unavailable) a defined manual-fallback procedure, not a hard failure.

## BC-15 Scoring *(Critical Offline Priority)*

- **Offline use case:** Recording scores/times/measurements at a venue with no connectivity.
- **Required local data:** The specific match/heat reference and assigned participants for the competition unit being scored.
- **Allowed offline actions:** Capture raw score records; make versioned corrections to locally captured (not-yet-synced) scores.
- **Prohibited offline actions:** **Score validation is never finalized offline** (see [high-integrity-domain-rules.md](high-integrity-domain-rules.md)) — validation requires the separation-of-duties check and server-side confirmation that this is not occurring.
- **Server authority:** BC-15 (server-side) is the authoritative store once synced; the device's local copy is provisional until acknowledged.
- **Conflict types:** Two devices independently capturing scores for the same competition unit (e.g., backup scorer and primary scorer); a device losing power mid-capture.
- **Conflict resolution concept:** Conflicting captures are **surfaced for human reconciliation**, never auto-merged or auto-selected, consistent with the "no silent mutation" principle.
- **Idempotency needs:** A score synced twice must not create duplicate score records.
- **Device identity:** Required, to distinguish which device/station captured a given score.
- **User identity:** Required, to identify which scorer entered a given score (separation-of-duties depends on this).
- **Time synchronization concern:** Capture timestamps matter for dispute resolution; local capture time should be retained alongside server-reconciled time.
- **Audit strategy:** Full scoring audit history retained regardless of offline capture.
- **Data encryption concern:** Lower sensitivity than credential data, but device loss/theft risk (RSK-07 in [Phase 0.1](../00-product/assumptions-constraints-risks.md)) still warrants at-rest protection.
- **Recovery process:** A device recovering connectivity after an extended outage should sync in a defined order (oldest-first) with duplicate detection.
- **Synchronization priority:** Highest — scores block downstream result certification.
- **Network degradation behavior:** Full offline capture capability; validation and certification wait for connectivity by design (not a degradation, an intentional boundary).

## BC-21 Medical Operations

- **Offline use case:** Logging medical incidents/injuries during an emergency, regardless of connectivity.
- **Required local data:** Minimal — ideally none beyond what's needed to identify the affected participant if already known; the workflow must function even without a prior local record.
- **Allowed offline actions:** Record incident, treatment, and referral locally.
- **Prohibited offline actions:** None inherently prohibited, but **cross-context propagation to Eligibility (BC-09) via the Anti-Corruption Layer only occurs once synced** — no direct offline linkage bypassing the ACL.
- **Server authority:** BC-21 server-side store is authoritative once synced.
- **Conflict types:** Duplicate incident entries for the same event from multiple responders.
- **Conflict resolution concept:** Duplicates are surfaced for review, not auto-merged, given the sensitivity of the data.
- **Idempotency needs:** High — duplicate incident records could distort medical trend data.
- **Device identity / user identity:** Required — restricted to Medical Team accounts.
- **Time synchronization concern:** Incident timing matters for continuity of care; retain local capture time.
- **Audit strategy:** Critical — access to medical records is itself audited, not just changes.
- **Data encryption concern:** **Highest** in the platform — this is the most sensitive data category (see [high-integrity-domain-rules.md](high-integrity-domain-rules.md)).
- **Recovery process:** Must have a defined paper/offline fallback if devices are entirely unavailable during an emergency (per [Phase 0.1 constraints](../00-product/assumptions-constraints-risks.md#2-constraints)).
- **Synchronization priority:** High, balanced against encryption/security requirements for sensitive data in transit.
- **Network degradation behavior:** Full offline capability is mandatory; this context cannot assume connectivity.

## BC-13 Technical Officials, BC-14 Venue and Schedule

- **Offline use case:** Officials and organizers referencing assignments/schedules at a venue.
- **Required local data:** Read-only cache of current assignments/schedule for the relevant venue/day.
- **Allowed offline actions:** View cached assignment/schedule data; officials may mark local acceptance (synced later).
- **Prohibited offline actions:** Assignment creation/reassignment and schedule finalization remain server-authoritative operations, not offline-originated.
- **Server authority:** BC-13/BC-14 server-side stores are authoritative; devices hold read-mostly caches.
- **Conflict types:** A schedule change occurring while a device's cache is stale.
- **Conflict resolution concept:** Devices should surface cache staleness (last-sync indicator) rather than presenting a schedule as guaranteed current.
- **Idempotency / device / user identity:** Standard.
- **Audit strategy:** Standard.
- **Data encryption concern:** Low.
- **Recovery process:** Simple re-sync on reconnect.
- **Synchronization priority:** Medium.
- **Network degradation behavior:** Read access degrades to last-known-good cache; writes wait for connectivity.

## BC-22 Billeting, BC-23 Food Services, BC-24 Transportation

- **Offline use case:** Check-in/out, meal distribution validation, and trip/boarding tracking at logistics points that may lack reliable connectivity.
- **Required local data:** Relevant assignment/entitlement cache (billeting assignments, meal entitlements, trip manifests) for the specific facility/route.
- **Allowed offline actions:** Record check-in/out, distribution, and boarding events locally.
- **Prohibited offline actions:** None of the routine logistics actions are prohibited offline; however, these contexts consume BC-20 Access Validation's scan events, which carry BC-20's own offline rules.
- **Server authority:** Respective owning context is authoritative once synced.
- **Conflict types:** Duplicate check-in/distribution events on reconnect.
- **Conflict resolution concept:** Idempotent event design (see below) prevents most conflicts from requiring manual resolution.
- **Idempotency needs:** Standard but important given high transaction volume at meal/transport points.
- **Device / user identity:** Standard.
- **Audit strategy:** Standard.
- **Data encryption concern:** Low–Medium.
- **Recovery process:** Standard re-sync.
- **Synchronization priority:** Medium.
- **Network degradation behavior:** Full offline capture with delayed sync is acceptable for these contexts.

## BC-27 ICT Service Operations, BC-25 Security Operations

- **Offline use case:** Logging support tickets and security incidents at venues during connectivity loss.
- **Required local data:** Minimal — these are primarily write-heavy, low-read-dependency workflows.
- **Allowed offline actions:** Create tickets/incidents locally.
- **Prohibited offline actions:** Incident escalation to external authorities (where applicable) may require connectivity and should not be assumed to have occurred until confirmed.
- **Server authority:** Respective context is authoritative once synced.
- **Conflict types:** Low — these are largely append-only logs.
- **Conflict resolution concept:** Minimal conflict risk; standard idempotent append.
- **Idempotency / device / user identity:** Standard.
- **Audit strategy:** Standard, elevated for security incidents.
- **Data encryption concern:** Low–Medium.
- **Recovery process:** Standard re-sync.
- **Synchronization priority:** High for security incidents (safety-relevant), medium for ICT tickets.
- **Network degradation behavior:** Full offline capture with delayed sync is acceptable.

---

## Actions That Must Never Become Final While Offline

Consistent with [high-integrity-domain-rules.md](high-integrity-domain-rules.md) and the working rules for this phase, the following are **structurally prohibited from being finalized on an offline device**, regardless of how confident the local decision appears:

- **Final eligibility approval** (BC-09) — unless a future, specifically authorized DepEd policy permits a defined offline-eligibility exception, which does not currently exist.
- **Official result certification** (BC-16).
- **Final protest resolution** (BC-17).
- **Medal tally certification** (BC-18).
- **Meet closure** (BC-04).
- **Destructive administrative changes** of any kind (e.g., deleting a participant record, revoking a role) — these require connectivity to the authoritative store and an accountable, connected actor.

Offline devices may **capture, cache, and locally sequence** actions leading toward these outcomes (e.g., a score can be captured offline, but its downstream certification cannot occur offline), consistent with the distinction drawn throughout this document between offline-tolerant *capture* and connectivity-required *finalization*.
