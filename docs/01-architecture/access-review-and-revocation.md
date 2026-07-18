# PMMS Access Review and Revocation

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [assignment-model.md](assignment-model.md) · [role-catalog.md](role-catalog.md) · [device-and-service-identity-model.md](device-and-service-identity-model.md)

This document defines periodic and event-triggered access review, and the revocation model for every identity/authority type in PMMS. **No specific review interval is invented here** — intervals are marked for policy validation, per working rule 32.

---

## Part 1 — Access Review and Recertification

### 1. Review Types

| Review Type | Scope | Typical Cadence (TBD) |
|---|---|---|
| Platform-role review | ROLE-01 through ROLE-05 | Frequent — these are the highest-blast-radius roles |
| Organization-admin review | ROLE-06 through ROLE-09 | Periodic |
| Meet-assignment review | All meet-scoped assignments | At assignment end and at meet closure, minimum |
| Sensitive-role review | Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer | Every meet cycle, minimum |
| Medical-access review | ROLE-38, ROLE-39 | Every meet cycle, elevated |
| Finance-access review | ROLE-45 | Every meet cycle |
| Support-access review | ROLE-04 and any granted impersonation sessions | Per session, plus periodic aggregate review |
| Dormant-account review | Any account with no activity for an extended period | Periodic |
| Device-credential review | All registered devices | Per meet, plus periodic |
| Service-account review | All Service Identities | Periodic, per [device-and-service-identity-model.md](device-and-service-identity-model.md) |
| Post-meet access review | All meet-scoped assignments and device credentials for a closed meet | At/after meet closure |

**No review interval is fixed in Phase 0.3.** Every "periodic"/"per meet cycle" reference above requires policy validation — see [access-open-decisions.md](access-open-decisions.md).

### 2. Review Triggers

Events that should trigger an access review, independent of any scheduled cadence:

- Assignment end (natural expiry)
- Meet closure (`MeetClosed` event)
- Personnel transfer (a person moves roles/organizations)
- Committee replacement (a new committee head takes over)
- Security incident (any incident involving access misuse or suspected compromise)
- Privileged-role grant (any Platform, Security, or high-integrity role grant should trigger a follow-up review within a defined window)
- Long inactivity (a dormant account is a named risk — unused privilege is unmonitored privilege)
- Device loss (see [device-and-service-identity-model.md, Section 4](device-and-service-identity-model.md#4-device-loss))
- Account compromise (suspected or confirmed)
- Organization offboarding (if PMMS later supports multiple organizations, per [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization))

### 3. Review Stakeholders

| Review Type | Reviewing Stakeholder (role to be identified) |
|---|---|
| Platform-role review | Security Administrator (ROLE-03), with DepEd Leadership sign-off |
| Sensitive meet-role review | Meet Director (ROLE-10), Technical Delegate (ROLE-26) for competition roles |
| Medical-access review | Medical Team lead, Data Privacy and Legal Stakeholders |
| Finance-access review | Finance Coordinator (ROLE-45), Organizing Committee |
| Support/impersonation review | Security Administrator (ROLE-03) |
| Device/service review | ICT Coordinator (ROLE-44), Security Coordinator (ROLE-43) |
| Audit-independent review | Audit Viewer (ROLE-05) — reviews the review process itself, per SOD-08 |

### 4. Sensitive-Role Review

High-integrity roles (Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer, Medical Officer) warrant review at minimum:
- Before each meet cycle begins (confirming the assignment is still appropriate).
- At meet closure (confirming no lingering active authority beyond what post-meet activities require).
- Immediately upon any related security incident or SoD-matrix violation flag.

### 5. Dormant Account Review

An account with no login/activity for an extended period is a named risk (unused privilege is a target for compromise without being noticed). Dormant-account review should identify such accounts and either confirm continued need, suspend, or revoke — the specific inactivity threshold is not fixed here (see [access-open-decisions.md](access-open-decisions.md)).

### 6. Organization Transfer

If a Person moves between organizational nodes (e.g., transferred to a different Division), their prior node-scoped assignments (per [scope-model.md](scope-model.md), non-inheriting across sibling nodes) should be reviewed for continued validity, not silently carried forward.

---

## Part 2 — Revocation Model

### 7. What Can Be Revoked

| Revocable Item | Revocation Effect |
|---|---|
| User account | Authentication itself is disabled; all active sessions terminated |
| Role | The role no longer activates through any assignment for this user (existing assignments referencing it become invalid) |
| Permission exception | A specific ad hoc grant beyond the role's normal permission set is withdrawn |
| Assignment | The specific scoped grant ends immediately (see [assignment-model.md, Section 4](assignment-model.md#4-assignment-lifecycle) — `Revoked` state) |
| Device | The device's credential is invalidated; it can no longer authenticate to the platform |
| API client | The client's credential/token is invalidated |
| Credential (accreditation) | The physical/digital credential is invalidated for access-scan purposes (see [permission-catalog.md](permission-catalog.md), `accreditation-credential.revoke`) |
| Offline token | A device's cached authorization snapshot is invalidated on next sync attempt |
| Support session | An active impersonation/support session is terminated |

### 8. Immediate Server-Side Revocation

For any connected client, revocation of the items above must take effect on the **very next** request evaluated by the server — there is no server-side grace period for a revoked identity/assignment/credential, consistent with [authorization-decision-model.md, Section 9](authorization-decision-model.md#9-revocation-considerations).

### 9. Offline Revocation Delay

For offline-capable devices, revocation propagation is subject to the lag window described in [offline-authorization-model.md, Section 10](offline-authorization-model.md#10-revocation-lag) — this is a disclosed, bounded, accepted risk (RSK-08 in [Phase 0.1](../00-product/assumptions-constraints-risks.md)), mitigated by treating revocations as the highest-priority sync content, never eliminated entirely for a genuinely offline-capable design.

### 10. Session Invalidation

Revoking a user account or a specific assignment should invalidate any active session(s) relying on that authority — a user mid-session when their Eligibility Approver assignment is revoked should lose the ability to perform further `eligibility-case.approve` actions immediately, even if their broader login session continues for other, unaffected purposes.

### 11. Token Invalidation

API tokens, mobile session tokens, and offline sync tokens are all independently revocable — revoking a user's password/login credential does not necessarily need to invalidate a separately-issued API token if that token has its own narrower, still-appropriate scope, and vice versa. Each credential type's invalidation is evaluated on its own terms.

### 12. Audit Preservation

Revocation **never** deletes the historical record of what the revoked identity/assignment/credential was authorized to do while it was active, or what it actually did — per [high-integrity-domain-rules.md](high-integrity-domain-rules.md), revocation is a new state transition in the record's history, not an erasure of the record itself.

### 13. Record Ownership Continuity

When a user account is revoked (e.g., staff departure), records they created or were the resource-owner of (registrations they encoded, results they were assigned to, etc.) remain intact and correctly attributed to them historically — revocation affects *future* authority, never *past* attribution.

### 14. Assignment Replacement

Revoking an assignment for operational reasons (not misconduct) — e.g., a Tournament Manager stepping down mid-meet — should be paired with creating a replacement assignment for continuity, following the same [assignment-model.md](assignment-model.md) lifecycle (a new `Draft`→`Active` assignment, not a reactivation of the old one under a different person).

### 15. Notification of Affected Administrators

Revoking a sensitive role/assignment should notify the relevant committee head/Meet Director (per [role-catalog.md](role-catalog.md)) so operational continuity can be planned — a silent revocation that leaves a critical workflow (e.g., Result Certification for an active sport) unstaffed is itself an operational risk, not just an access-control concern.

---

## Open Questions

- Specific review intervals for every review type in Part 1 (marked TBD throughout).
- Dormant-account inactivity threshold (Section 5).
- Whether assignment replacement (Section 14) requires the same approval chain as the original assignment or an expedited continuity process.

Tracked in [access-open-decisions.md](access-open-decisions.md).
