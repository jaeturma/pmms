# PMMS Authorization Decision Model

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [scope-model.md](scope-model.md) · [assignment-model.md](assignment-model.md) · [separation-of-duties-matrix.md](separation-of-duties-matrix.md)

This document defines the conceptual decision sequence PMMS uses to determine whether a requested action may proceed. **No Laravel Gate, Policy class, or middleware implementation is defined here** — this is the decision logic those later artifacts must faithfully implement.

---

## 1. Policy Inputs

Every authorization decision conceptually consumes:

User ID · Account status · Identity assurance level · Role · Permission · Assignment · Assignment status · Scope (Organization, Meet, Committee, Delegation, Sport, Event, Venue, Tournament unit) · Resource owner · Resource status · Data classification · Time · Device identity · Network/offline state · Conflict declaration · Previous participation in the workflow (for separation-of-duties checks) · Required approval level · Security restrictions · Emergency mode · Explicit denial.

## 2. Decision Sequence

An action may proceed only when **all** of the following hold, evaluated in order (an early failure short-circuits the remaining checks):

1. **Authentication** — the identity is authenticated where required (not required for Public scope actions).
2. **Account status** — the account is active (not suspended, locked, or revoked).
3. **No standing restriction** — the user is not subject to a suspension or revocation affecting this specific action.
4. **Permission mapping** — the requested action maps to an explicit, named permission (never an implicit "admin can do anything" fallback).
5. **Role qualification** — the user holds a role that is a candidate for this permission (see [permission-catalog.md](permission-catalog.md), "Required Role Candidates").
6. **Assignment validity** — where the permission requires an assignment (nearly all non-Platform permissions do), the role is active through a currently-valid Assignment (see [assignment-model.md](assignment-model.md) lifecycle states — must be `Active`, not `Draft`/`PendingApproval`/`Suspended`/`Expired`/`Revoked`).
7. **Scope match** — the requested resource falls within the assignment's scope (see [scope-model.md](scope-model.md) — may require multiple scope dimensions to match simultaneously).
8. **Resource-state check** — the resource is in a state where this action is legally possible (e.g., cannot certify a result that has already been certified; cannot lock entries that are already locked).
9. **Data classification check** — the requester's clearance (via role/assignment) permits the resource's classification tier (see [phase-0.3, Section 21](phase-0.3-access-and-assignment-architecture.md#21-data-classification-model)).
10. **Separation-of-duties check** — the requester was not the initiating actor for an action requiring a distinct approver (see [separation-of-duties-matrix.md](separation-of-duties-matrix.md)).
11. **Time validity** — the current time falls within the assignment's/credential's validity window (and, for shift-scoped actions, the current shift).
12. **Device trust check** — where the action requires a specific trusted device (e.g., a registered scanner), the requesting device matches (see [device-and-service-identity-model.md](device-and-service-identity-model.md)).
13. **Meet-status check** — the meet is in a lifecycle state that permits this action (e.g., no write actions after `MeetClosed` except explicitly defined post-closure exceptions).
14. **No explicit denial or security hold** — no standing restriction, security hold, or explicit deny rule overrides the otherwise-granted access (see Section 5, Precedence, below).
15. **Approval-level check** — the action's required approval tier (Section 19 of the main document) is satisfied by the requester's role/assignment combination.
16. **Audit logging** — the decision (allow or deny) and, on allow, the resulting action are logged at the audit level appropriate to the action's risk tier (see [permission-catalog.md](permission-catalog.md) "Audit" column).

**Deny-by-default:** if any step fails, the action is denied. There is no fallback "probably fine" path — this is the explicit application of working rule 26 ("prefer explicit deny behavior for highly sensitive operations"), applied here to *all* steps, not only the sensitive ones, because a partial-failure "mostly allow" state is itself a security defect.

## 3. Explicit Deny Precedence

```text
Security Hold or Explicit Denial
overrides
Temporary Grant, Assignment, Role, or Inherited Scope
```

A security hold (e.g., an account flagged during an active security investigation) or an explicit denial (e.g., a specific user explicitly barred from a specific action following an incident) **always** wins over any grant, regardless of how senior the role or how broad the scope. This is evaluated at Step 14 above but conceptually sits "above" the rest of the sequence — a security hold should short-circuit evaluation as early as technically practical in a later implementation, not merely as the last check performed.

## 4. Sensitive Permissions Require Explicit Grant

Consistent with working rule 26 and the least-privilege principle, sensitive permissions (Critical risk tier in [permission-catalog.md](permission-catalog.md)) should generally require an **explicit** assignment-based grant rather than being inferred from a broader role. A Meet Director does not implicitly gain `official-result.certify` by virtue of being Meet Director — that permission requires its own Result Certifier assignment, explicitly granted, even if the same individual happens to hold both.

## 5. Decision Table — Representative Scenarios

| # | Scenario | Auth? | Account Active? | Assignment Valid? | Scope Match? | Resource State OK? | SoD OK? | Result |
|---|---|---|---|---|---|---|---|---|
| 1 | Result Certifier (Athletics, Track) certifies a Track result, all scores validated | Yes | Yes | Yes | Yes | Yes (Generated, not yet Certified) | Yes (not the validating official) | **Allow** |
| 2 | Same certifier attempts to certify a Field Events result | Yes | Yes | Yes | **No** (Event scope mismatch) | — | — | **Deny** |
| 3 | Technical Official attempts to validate a score they themselves entered | Yes | Yes | Yes | Yes | Yes | **No** (SOD-02) | **Deny** |
| 4 | Eligibility Approver attempts to approve a case they reviewed themselves | Yes | Yes | Yes | Yes | Yes | **No** (SOD-01) | **Deny** |
| 5 | Access Control Operator scans a credential at their assigned gate during their assigned shift, device offline, using cached credential set | Yes (cached) | Yes | Yes (cached) | Yes | Yes | N/A | **Allow (offline, provisional)** — see [offline-authorization-model.md](offline-authorization-model.md) |
| 6 | Same operator attempts to scan at a different gate | Yes | Yes | Yes | **No** (Device scope mismatch) | — | — | **Deny** |
| 7 | Suspended Coach account attempts to submit a competition entry | Yes | **No** (Suspended) | — | — | — | — | **Deny** |
| 8 | Meet Director attempts to close a meet with an unresolved protest | Yes | Yes | Yes | Yes | **No** (resource state: open protest) | — | **Deny** |
| 9 | Public anonymous visitor requests a certified-but-unpublished result | N/A (no auth required for public scope) | N/A | N/A | N/A (Public scope) | **No** (resource not in Published state) | — | **Deny** |
| 10 | Security Administrator, under an active security hold flag on their own account (e.g., pending investigation), attempts to approve an emergency access grant | Yes | Yes | Yes | Yes | Yes | — | **Deny** — explicit denial/security hold overrides (Section 3) |
| 11 | Support Administrator requests an impersonation session without a logged reason | Yes | Yes | N/A (feature disabled by default) | — | — | — | **Deny** — impersonation requires reason + approval (Section 32 of the main document) |
| 12 | Medal Tally Certifier attempts to certify a tally where the same user encoded the recalculation | Yes | Yes | Yes | Yes | Yes | **No** (SOD-04) | **Deny** |

## 6. User-Facing Denial Behavior

A denied action should surface a reason category (e.g., "outside your assigned scope," "requires approval from another role," "resource is not in the correct state") without exposing sensitive details about *why* in a way that could itself leak protected information (e.g., a denial for viewing a Restricted eligibility case should not confirm or deny the case's existence to an unauthorized user). Precise denial-message design is a later UI-phase concern; the architectural requirement here is only that denial is **never silent** to the requester in a way that looks like a system error rather than an access decision.

## 7. Logging Behavior

Every decision at Critical and Elevated audit levels (per [permission-catalog.md](permission-catalog.md)) is logged **regardless of outcome** — both allowed and denied attempts at sensitive actions are audit-relevant, since a pattern of denied attempts can itself be a security signal (see [device-and-service-identity-model.md](device-and-service-identity-model.md) and [access-review-and-revocation.md](access-review-and-revocation.md)).

## 8. Caching Considerations

Authorization decisions may be cached for performance (a later-phase concern), but any cache must respect Section 5 of [scope-model.md](scope-model.md) — scope expiry must take effect immediately for sensitive actions, meaning any cache for Critical/High-risk permissions must either not cache at all or use an extremely short, actively-invalidated window. Lower-risk, high-volume reads (e.g., public schedule display) may cache far more liberally, consistent with the eventual-consistency treatment established in [phase-0.2-domain-architecture.md, Section 9](phase-0.2-domain-architecture.md#9-transaction-and-consistency-boundaries).

## 9. Revocation Considerations

When a role, permission, assignment, or credential is revoked, the decision model must reflect that revocation on the **next** evaluated request — no decision may rely on a stale grant once revocation has been recorded server-side. Offline/cached scenarios have a bounded, documented exception window (see [offline-authorization-model.md](offline-authorization-model.md)); this is a deliberate, narrow exception, not a general allowance for stale authorization.

## 10. Emergency Mode

When an Emergency Assignment (per [assignment-model.md, Section 7](assignment-model.md#7-emergency-assignments)) is active, the decision sequence above still applies in full — emergency access is still an **explicit, scoped, time-boxed grant**, evaluated the same way as any other assignment, not a bypass of the sequence itself. What differs is *how quickly* such a grant can be created (see [phase-0.3-access-and-assignment-architecture.md, Section 31](phase-0.3-access-and-assignment-architecture.md#31-emergency-and-break-glass-access)), not whether the resulting access still goes through Steps 1–16 above.
