# ADR-0003: Hybrid RBAC + Attribute/Scope/Assignment/State Authorization Model

## Status

Accepted (as a Phase 0.3 access-architecture decision; pending formal security, domain, and stakeholder sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 established PMMS as a domain-oriented modular monolith with 34 explicit bounded contexts, 13 of them Core and 11 of the platform's domains classified as high-integrity (requiring human validation, no silent mutation, and separation of duties). That decision left open how PMMS determines, for any given request, whether the requesting identity is actually authorized to perform the requested action.

Conventional single-tenant Role-Based Access Control — "a user holds a role; a role has permissions; if you hold the role, you have the permission" — is demonstrably insufficient for PMMS's actual operating reality: the platform spans multiple meets running independently with no cross-meet authority bleed; committee, sport, venue, and event-scoped assignments that exist only for a bounded time; eleven high-integrity domains that require separation of duties beyond what a role check alone can express (the same "Tournament Manager" role must not let one individual both schedule a match and certify its result without a distinct, separately-granted authority); a hard public/operational data boundary; offline field operations where authorization decisions must function without a live connection to the server; and non-human identities (devices, services, AI features) that must never be conflated with human accounts or granted authority a human account did not itself possess.

Building the application layer directly against a flat RBAC assumption would require expensive retrofitting the first time any of these requirements surfaced in practice — most likely at the first multi-meet pilot, or the first high-integrity incident where "the Tournament Manager also certified their own result" turns out to have been structurally possible.

## Decision

PMMS will use a **hybrid authorization model combining Role-Based Access Control, scoped Assignments, resource attributes, workflow state, data classification, and explicit separation-of-duties controls.**

Specifically:

1. **Identity is layered and never conflated.** Person, Participant Profile, User Account, Device Identity, Service Identity, and AI Assistant Execution Identity are distinct concepts (see [../../docs/01-architecture/identity-model.md](../../docs/01-architecture/identity-model.md)). A Person may have no User Account — the default case for minor athletes.
2. **Role and Assignment are distinct.** A Role (53 catalogued — [../../docs/01-architecture/role-catalog.md](../../docs/01-architecture/role-catalog.md)) is a reusable description of responsibility. An Assignment (14 types — [../../docs/01-architecture/assignment-model.md](../../docs/01-architecture/assignment-model.md)) is the time-bound, scoped fact that a specific user holds that role. A role with no active assignment grants nothing.
3. **Permissions represent business actions**, not raw CRUD (~115 catalogued — [../../docs/01-architecture/permission-catalog.md](../../docs/01-architecture/permission-catalog.md)), using a `resource.action` naming standard that keeps `approve`/`certify`/`publish`/`override`/`reopen`/`revoke` always distinct from a generic `edit`.
4. **Scope is explicit and does not inherit broad-to-narrow for sensitive actions** (18 scope types across two largely independent hierarchies plus non-hierarchical dimensions — [../../docs/01-architecture/scope-model.md](../../docs/01-architecture/scope-model.md)). Organization scope does not grant Meet authority; Meet scope does not grant Sport authority; Committee scope does not cross committees.
5. **Effective authorization is a 16-step decision sequence** — authentication, account status, permission mapping, role qualification, assignment validity, scope match, resource-state check, data-classification check, separation-of-duties check, time validity, device trust, meet-status check, explicit-denial/security-hold precedence, approval-level check, and audit logging — never simplified to "user has role" (see [../../docs/01-architecture/authorization-decision-model.md](../../docs/01-architecture/authorization-decision-model.md)).
6. **Separation of duties is a named, catalogued control** (11 entries — [../../docs/01-architecture/separation-of-duties-matrix.md](../../docs/01-architecture/separation-of-duties-matrix.md)), most critically: the entering scorer is never the validator; the eligibility case reviewer is never its approver; a result certifier recuses from resolving a protest against their own certification; the tally encoder is never the tally certifier.
7. **High-integrity domains carry dedicated access controls** (13 domains — [../../docs/01-architecture/high-integrity-access-controls.md](../../docs/01-architecture/high-integrity-access-controls.md)) extending the Phase 0.2 domain-level safeguards with concrete role/scope/assignment gating.
8. **Offline authorization is bounded and provisional.** Cached authorization snapshots are narrowly scoped, device-bound, and time-limited; offline actions are classified Provisional (default) or Final (reserved for low-risk, high-volume actions only); eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, and high-risk overrides are never finalized offline under any circumstance (see [../../docs/01-architecture/offline-authorization-model.md](../../docs/01-architecture/offline-authorization-model.md)).
9. **AI authority is bounded by the requesting identity.** An AI-assisted feature's effective access is the intersection of its own scope and the requesting user's authority, never a union — AI never independently decides a high-integrity outcome (see [../../docs/01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 29](../../docs/01-architecture/phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary)).
10. **Break-glass emergency access and support impersonation are not assumed necessary.** Both are recorded as open questions requiring explicit security and stakeholder decision (AD-09, AD-10 in [../../docs/01-architecture/access-open-decisions.md](../../docs/01-architecture/access-open-decisions.md)) rather than default capabilities to build toward.

**Explicitly not decided by this ADR:** specific Laravel package selection for role/permission storage, Gate/Policy class design, middleware implementation, database schema for any identity/role/permission/assignment table, specific authentication mechanism beyond confirming the existing Fortify-based scaffolding as the starting foundation, and specific numeric parameters (review intervals, offline snapshot validity durations, credential rotation cadence) — all recorded as open decisions requiring further validation.

## Rationale

- **Matches PMMS's actual operating shape.** Multi-meet, multi-committee, multi-sport, multi-venue, offline-capable, high-integrity operations cannot be safely expressed by flat RBAC — every one of Phase 0.1's named high-integrity domains (scoring/results, eligibility, medical, accreditation, medal tally) requires exactly the kind of scope-and-assignment-aware, separation-of-duties-enforcing model this ADR establishes.
- **Prevents the single most dangerous authorization anti-pattern up front.** Working rule 15 ("do not use one global role to represent all meet-specific authority") and the explicitly named anti-pattern ("Tournament manager automatically certifying results") are exactly the failure modes flat RBAC invites; this model makes them structurally difficult rather than merely discouraged by convention.
- **Keeps Phase 0.4 unblocked on the parts that matter.** Every workflow candidate from Phase 0.2's [workflow-and-command-catalog.md](../../docs/01-architecture/workflow-and-command-catalog.md) now has a named actor-role mapping, even where the specific individual authority (e.g., who exactly certifies a result) remains blocked on a Phase 0.1 policy decision — the *shape* of the authorization model does not need to wait for DepEd's answer, only the *content* of a few specific role assignments does.
- **Builds on, rather than replaces, the existing repository foundation.** The confirmed Laravel 13 + Fortify scaffolding (password, 2FA, passkeys) is retained as the authentication foundation; this ADR adds the *authorization* layer on top, consistent with the recommendation that a Laravel-compatible role/permission package may provide storage/query mechanics while the scope-aware decision logic is built as an explicit policy layer.

## Consequences

**Positive:**
- Phase 0.4 (Application, Integration, and Runtime Architecture) inherits a complete, internally consistent role/permission/scope/assignment vocabulary and can design concrete policy classes, database schema, and API authorization middleware against it without re-deriving the model from first principles.
- High-integrity workflows have a named, catalogued separation-of-duties control before any code exists to violate it.
- The identity-layering distinction (Person/Participant/User Account) directly protects minor-athlete data by ensuring the default case (no login for a minor) is the architecturally natural one, not a bolt-on exception.

**Negative / trade-offs:**
- A hybrid model is more complex to implement correctly than flat RBAC — every sensitive action requires explicit scope and assignment validation, not just a role check, which is more implementation and test effort per feature.
- Several high-integrity roles (Eligibility Approver, Technical Delegate, Result Certifier, Tally Certifier) cannot have their specific authority finalized until DepEd resolves the corresponding Phase 0.1 policy decisions, meaning Phase 0.4's policy implementation for those specific workflows will need to accommodate a placeholder authority pending that resolution.
- 53 roles and ~115 permissions is a substantial catalog to maintain consistently; this is mitigated by the naming standard and governance question raised in AD-22, but remains a real ongoing discipline requirement.
- Two topics (break-glass access, support impersonation) are deliberately left without a recommended default, which means Phase 0.4 cannot assume either capability exists — this is a correct reflection of genuine uncertainty, not a documentation gap, but it does mean some later design work is contingent on decisions this ADR does not make.

## Alternatives Considered

1. **Flat RBAC (role determines all authority, no scope/assignment layer).** Rejected — cannot express cross-meet isolation, temporary committee assignments, or separation of duties; directly recreates the named anti-patterns this phase exists to prevent.
2. **Pure Attribute-Based Access Control (ABAC) with no role concept at all.** Rejected — while more flexible in theory, a pure-ABAC model sacrifices the human-readability and reusability that named roles provide (e.g., "Tournament Manager" is immediately meaningful to a DepEd stakeholder; an arbitrary attribute-rule set is not), and PMMS's roles are genuinely reusable across meets in a way that justifies keeping them as first-class concepts.
3. **Per-meet role duplication (e.g., "Tournament Manager – Meet 2027" as a distinct role from "Tournament Manager – Meet 2028").** Rejected — explicitly named anti-pattern (working rule 15, "one global role... " inverted to its opposite failure: role-per-meet explosion); the Assignment concept exists specifically to avoid this.
4. **Assume break-glass and impersonation are required from the outset.** Rejected as a default — both are real capabilities with real risk; assuming them necessary without evidence would build unneeded attack surface. Recorded as open decisions (AD-09, AD-10) instead of defaults.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated security architect/administrator and domain-expert reviewers, per [../../docs/01-architecture/README.md, "Ownership and Review Expectations"](../../docs/01-architecture/README.md#ownership-and-review-expectations).
- Resolution of the Phase 0.1 policy decisions this model's high-integrity role identities depend on: [OD-07](../../docs/00-product/open-decisions.md#od-07--eligibility-authority), [OD-08](../../docs/00-product/open-decisions.md#od-08--official-result-approval-chain), [OD-09](../../docs/00-product/open-decisions.md#od-09--protest-and-appeal-authority), [OD-12](../../docs/00-product/open-decisions.md#od-12--medal-tally-rules).
- Resolution of the highest-priority Phase 0.3 open decisions: AD-06 (maximum concurrent high-integrity assignments), AD-09 (impersonation necessity), AD-10 (break-glass necessity — no recommended direction), per [../../docs/01-architecture/access-open-decisions.md, "Summary of High-Priority Open Decisions"](../../docs/01-architecture/access-open-decisions.md#summary-of-high-priority-open-decisions).

## Related Documents

- [../../docs/01-architecture/phase-0.3-access-and-assignment-architecture.md](../../docs/01-architecture/phase-0.3-access-and-assignment-architecture.md)
- [../../docs/01-architecture/identity-model.md](../../docs/01-architecture/identity-model.md)
- [../../docs/01-architecture/role-catalog.md](../../docs/01-architecture/role-catalog.md)
- [../../docs/01-architecture/permission-catalog.md](../../docs/01-architecture/permission-catalog.md)
- [../../docs/01-architecture/scope-model.md](../../docs/01-architecture/scope-model.md)
- [../../docs/01-architecture/assignment-model.md](../../docs/01-architecture/assignment-model.md)
- [../../docs/01-architecture/authorization-decision-model.md](../../docs/01-architecture/authorization-decision-model.md)
- [../../docs/01-architecture/separation-of-duties-matrix.md](../../docs/01-architecture/separation-of-duties-matrix.md)
- [../../docs/01-architecture/access-open-decisions.md](../../docs/01-architecture/access-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../security-rules.md](../security-rules.md)
- [../authorization-rules.md](../authorization-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
