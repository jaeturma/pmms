# PMMS Offline Authorization Model

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) (Phase 0.2, domain-level) · [device-and-service-identity-model.md](device-and-service-identity-model.md) · [authorization-decision-model.md](authorization-decision-model.md)

This document extends the Phase 0.2 domain-level offline boundaries with **authorization-specific** offline behavior: what a device is allowed to decide on its own, using what cached information, and for how long, before it must defer to the server. **Offline clients must never permanently expand authority** (working rule 29) — every offline grant described here is provisional, bounded, and revalidated.

---

## 1. Offline Authorization Snapshot

Before operating offline, a device holds a **snapshot**: a cached, time-stamped copy of the specific roles, assignments, and scope grants relevant to that device's operational purpose (e.g., an Access Control Operator's device caches the current credential-validity set from Accreditation, plus its own operator's assignment validity — nothing more).

- The snapshot is **narrowly scoped** to what that specific device/role needs, never a full dump of platform authorization state.
- The snapshot carries its own **validity period**, distinct from the underlying assignment's validity period — a snapshot older than its validity period is treated as stale and requires either resync or a defined degraded-operation mode (see Section 9).

## 2. Cached Roles and Assignments

- Only **currently Active** assignments (per [assignment-model.md](assignment-model.md) lifecycle) are included in a snapshot — a `Suspended`, `Expired`, or `Revoked` assignment is never cached as if active.
- The snapshot includes the assignment's own end-date/time, so the device can locally recognize when an assignment it cached has since expired, even without a fresh sync.

## 3. Validity Period

- Different offline contexts warrant different snapshot validity periods based on their risk profile (per [permission-catalog.md](permission-catalog.md) risk tiers) — a high-volume Access Validation snapshot might be valid for a single operational day, while a lower-risk logistics snapshot might tolerate a longer window. **Specific durations are not fixed in Phase 0.3** — see [access-open-decisions.md](access-open-decisions.md).
- Regardless of duration, **Critical-risk actions never rely on an offline snapshot for finality** (Section 8 below) — the snapshot only ever supports *provisional* decisions for those actions.

## 4. Device Binding

An offline authorization snapshot is bound to the specific **Device Identity** it was synced to (per [device-and-service-identity-model.md](device-and-service-identity-model.md)) — a snapshot cannot be copied to a different device and remain valid, since device trust is an independent input to the authorization decision (see [authorization-decision-model.md, Step 12](authorization-decision-model.md#2-decision-sequence)).

## 5. User Reauthentication

Where a device is shared across shifts/operators (per the shared-device risk noted in [device-and-service-identity-model.md](device-and-service-identity-model.md#3-device-trust-principles)), the operating User Account must reauthenticate at the start of each session, even while the device itself remains offline — a cached device-level trust does not substitute for a fresh operator-level authentication check where feasible (mechanism for offline reauthentication, e.g., a locally-verifiable PIN/passkey, is a later-phase implementation concern).

## 6. Offline Action Classification

| Classification | Meaning |
|---|---|
| **Provisional** | The action is recorded locally and treated as pending until server confirmation; it may still be reversed/corrected on sync if a conflict is found. |
| **Final** | The action is treated as complete and binding the moment it's performed, with no further confirmation step — **reserved exclusively for low-risk, high-volume actions** where the cost of delay outweighs the cost of a rare correction (e.g., a routine meal-entitlement validation). |

**By default, offline actions in PMMS are Provisional, not Final**, for anything above Low risk tier (per [permission-catalog.md](permission-catalog.md)).

## 7. Potential Offline-Allowed Actions (Provisional)

Consistent with [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) (Phase 0.2):

- Capture score draft (`score-record.submit`)
- Record access scan (`access-scan.validate`)
- Record medical incident (`medical-encounter.record`, `medical-incident.report`)
- Record transport arrival
- Validate meal entitlement from cached data (`meal-entitlement.validate`)
- Record venue incident (`logistics-incident.record`, `security-incident` reporting)

Each of these is **provisional** — it is captured locally, attributed to the device and operator, and confirmed/reconciled on the next sync.

## 8. Actions Requiring Server Confirmation (Never Final Offline)

Directly inherited from [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) and reinforced at the authorization layer:

- Final eligibility approval (`eligibility-case.approve`/`.reject`)
- Result certification (`official-result.certify`)
- Protest resolution (`protest.resolve`)
- Medal tally certification (`medal-tally.certify`)
- Meet closure (`meet.close`)
- Privilege grants (any new Assignment creation/approval)
- Permanent credential revocation (`accreditation-credential.revoke` as a final, non-reversible action — an *offline* revocation attempt should instead be treated as a request queued for server confirmation, or handled through the emergency/override path in Section 12)
- High-risk overrides (`access-scan.override`, `progression.override`)

No exception to this list exists in Phase 0.3 (see [domain-open-decisions.md, DD-19](domain-open-decisions.md#dd-19--offline-finality-rules), which recommends "no exceptions" as the default architectural position).

## 9. Server Revalidation

On reconnection, every provisional offline action is revalidated against current server-side state before being treated as final:

- Was the operator's assignment still Active for the entire offline period, or did it expire/get revoked mid-session?
- Did the resource the action targeted change state in a conflicting way while offline (e.g., a match was rescheduled while a device offline-cached the old schedule)?
- Is there a duplicate of this action already recorded (idempotency check, Section 11)?

## 10. Revocation Lag

Between a server-side revocation (of a credential, assignment, or device) and an offline device receiving that revocation, a **lag window** exists during which the offline device may still act on stale authority. This is a named, accepted risk (not eliminated, only bounded):

- **Mitigation:** revocations are treated as the highest-priority sync content — a device should pull revocation updates before any other sync category whenever any connectivity (even brief/low-bandwidth) is available (per [offline-and-synchronization-boundaries.md, BC-20 section](offline-and-synchronization-boundaries.md#bc-20-access-validation-critical-offline-priority)).
- **Residual risk acceptance:** the lag window can never be reduced to zero for a genuinely offline-capable system; this is disclosed as RSK-08 in [Phase 0.1](../00-product/assumptions-constraints-risks.md) and tracked, not hidden.

## 11. Conflict Handling and Idempotency

- Conflicting offline-originated data (e.g., two devices independently capturing scores for the same match) is **surfaced for human reconciliation**, never auto-merged or auto-selected (per [high-integrity-domain-rules.md](high-integrity-domain-rules.md), "no silent mutation").
- Every offline-originated record carries enough information (device identity, operator identity, local timestamp) to detect and discard exact duplicates on resync without human intervention, while genuine conflicts (different values, not just a resent duplicate) still require review.

## 12. Time Drift

Offline device clocks may drift from server time during extended disconnection. Local capture timestamps are retained as recorded, and a server-reconciled timestamp is additionally attached on sync — both are preserved (not overwritten) so that dispute resolution can reference either, per [high-integrity-domain-rules.md](high-integrity-domain-rules.md) evidence-retention principles.

## 13. Expired Authorization (While Offline)

If a cached assignment's end-date passes while the device is still offline, the device should locally recognize this (from the cached expiry date, Section 2) and **stop presenting that assignment's actions as available**, even without a fresh server sync — this is a client-side responsibility that flows from the snapshot's own self-describing validity data, not a claim that the client can independently verify server-side revocation.

## 14. Lost Device (Offline)

A device lost while offline poses the highest residual risk in this model, since its cached snapshot cannot be immediately invalidated. Mitigations:

- Snapshot validity periods (Section 3) bound the maximum exposure window.
- Device Identity revocation (Section 8 of [device-and-service-identity-model.md](device-and-service-identity-model.md)) takes effect the moment any subsequent sync attempt occurs from that device — a lost device cannot "resync" its way back into good standing.
- Where the lost device held Critical-risk cached data (e.g., a full credential-validity set), the response should include treating that data as compromised and considering credential-set rotation, not merely device revocation.

## 15. Recovery Behavior

On reconnection after an extended offline period, a device should:

1. Sync revocations first (Section 10).
2. Sync provisional actions in a defined order (oldest-first, per [offline-and-synchronization-boundaries.md, BC-15 section](offline-and-synchronization-boundaries.md#bc-15-scoring-critical-offline-priority)) with duplicate detection.
3. Refresh its authorization snapshot before resuming any further offline-capable operation.
4. Surface any conflicts found during sync for human review (Section 11) rather than silently resolving them.

## 16. Emergency Offline Access

Where an Emergency Assignment (per [assignment-model.md, Section 7](assignment-model.md#7-emergency-assignments)) needs to be granted while offline (e.g., a Medical emergency at a venue with no connectivity), the granting action itself should still follow the full [authorization-decision-model.md](authorization-decision-model.md) sequence **as far as locally possible**, be clearly marked Provisional, and require server confirmation and a mandatory post-incident review the moment connectivity resumes — it is not exempted from Section 8's "never final offline" list merely because it is an emergency.

## 17. High-Integrity Restrictions (Summary)

This document does not weaken any restriction from [high-integrity-domain-rules.md](high-integrity-domain-rules.md) (Phase 0.2) or [high-integrity-access-controls.md](high-integrity-access-controls.md) (Phase 0.3) — it specifies *how* offline capture coexists with those restrictions (via the Provisional/Final distinction) rather than creating any offline carve-out from them.

## 18. Open Questions

- Specific snapshot validity durations per context (Section 3).
- Offline reauthentication mechanism for shared devices (Section 5).
- Whether a formal "extended outage" policy is needed for venues with days of no connectivity (relates to [domain-open-decisions.md, DD-19](domain-open-decisions.md#dd-19--offline-finality-rules)).

Tracked in [access-open-decisions.md](access-open-decisions.md).
