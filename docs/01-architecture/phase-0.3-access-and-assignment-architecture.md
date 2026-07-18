# PMMS Phase 0.3 — User, Role, Permission, Scope, and Assignment Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.3 — User, Role, Permission, Scope, and Assignment Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.3 — User, Role, Permission, Scope, and Assignment Architecture |
| Version | 0.3.0 |
| Status | Draft Complete — Pending Security, Domain, and Stakeholder Validation |
| Date | 2026-07-14 |
| Intended audience | Software architects, security engineers, Laravel developers, React developers, Flutter developers, QA engineers, DevOps engineers, tournament managers, committee heads, DepEd stakeholders, future system administrators |
| Related documents | [identity-model.md](identity-model.md), [role-catalog.md](role-catalog.md), [permission-catalog.md](permission-catalog.md), [scope-model.md](scope-model.md), [assignment-model.md](assignment-model.md), [authorization-decision-model.md](authorization-decision-model.md), [separation-of-duties-matrix.md](separation-of-duties-matrix.md), [high-integrity-access-controls.md](high-integrity-access-controls.md), [device-and-service-identity-model.md](device-and-service-identity-model.md), [offline-authorization-model.md](offline-authorization-model.md), [access-review-and-revocation.md](access-review-and-revocation.md), [access-open-decisions.md](access-open-decisions.md), [README.md](README.md), [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md), [../00-product/phase-0.1-product-foundation.md](../00-product/phase-0.1-product-foundation.md), [../../.ai/authorization-rules.md](../../.ai/authorization-rules.md), [../../.ai/security-rules.md](../../.ai/security-rules.md), [../../.ai/decisions/ADR-0003-role-permission-scope-and-assignment-architecture.md](../../.ai/decisions/ADR-0003-role-permission-scope-and-assignment-architecture.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.3.0 | 2026-07-14 | Initial Phase 0.3 draft: identity model, role/permission catalogs, scope and assignment architecture, authorization decision model, separation-of-duties matrix, high-integrity access controls, device/service identity, offline authorization, access review/revocation, and open decisions, built from the approved Phase 0.1 product foundation and Phase 0.2 domain architecture. |

---

## 2. Executive Summary

PMMS cannot be secured with conventional, single-tenant role-based access control (RBAC) — "a user has a role, a role has permissions" is not expressive enough for what this platform actually is. PMMS spans **multiple organizations** (at least architecturally, per [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)), **multiple meets** running independently, **temporary committee assignments** that exist only for the duration of one meet, **sport-specific authority** that must not bleed across sports, **venue-specific authority** tied to physical presence, **high-integrity official decisions** (eligibility, scoring, results, protests, medal tally) that require separation of duties and auditability beyond what any role check alone can express, a hard **public/operational data boundary**, **offline field operations** where authorization decisions must work without a live connection to the server, **mobile devices** and **service identities** that are not people at all, and a stated **commercial multi-organization readiness** goal from Phase 0.1.

A flat RBAC model — where holding the "Tournament Manager" role means you can do everything a Tournament Manager can ever do, everywhere, forever — fails every one of these requirements simultaneously. It cannot express that a Tournament Manager for Basketball in Meet 2027 has no authority whatsoever in Meet 2028, or in Volleyball within the same meet. It cannot express that holding a role does not by itself imply an active, time-bound grant to exercise it. It cannot express that the person entering a score must not be the same person certifying the result derived from it.

**PMMS should use Role-Based Access Control enhanced by Attribute-Based, Scope-Based, Assignment-Based, and State-Based authorization.** Concretely, an effective grant of authority requires: an authenticated identity, an active account, a qualifying role, a specific permission that role is a candidate for, a valid time-bound assignment activating that role within a specific scope, the target resource being in a state where the action is legally possible, the requester's data-classification clearance, satisfaction of separation-of-duties rules, and the absence of any explicit denial or security hold. This is deliberately more than "user has role" — see the Authority formula and the full decision sequence in [authorization-decision-model.md](authorization-decision-model.md).

## 3. Architecture Goals

Least privilege · Explicit scope for every sensitive action · Temporary operational authority via Assignments, not permanent role grants · Reusable roles across meets and organizations · Strict separation of permanent identity from meet-specific assignment · Support for multiple simultaneous responsibilities per user · High-integrity approval controls with enforced separation of duties · Auditable decisions at every sensitive step · Secure mobile and device access with narrowly scoped device trust · Offline-safe authorization that never permanently expands authority · Commercial multi-organization readiness (tenant-scoping designed in, not retrofitted) · Maintainable, centralized policy evaluation rather than scattered ad hoc checks · Testable authorization rules expressible as concrete scenarios · Human-readable permission intent (business action names, not technical CRUD).

## 4. Authorization Principles

1. **Deny by default** — no action is implicitly allowed; every allow is the result of an explicit, traceable grant.
2. **Least privilege** — grants are as narrow as the real responsibility requires, never broader for convenience.
3. **Explicit authority** — "probably has access" is not a valid state; authority is always attributable to a specific role + permission + scope + assignment combination.
4. **Scope before action** — an action is never evaluated without first establishing the scope it applies within.
5. **Assignment validity** — a role's capabilities are inert until activated by a currently-valid Assignment.
6. **Separation of duties** — no individual both initiates and approves/certifies the same high-integrity transaction (see [separation-of-duties-matrix.md](separation-of-duties-matrix.md)).
7. **Resource-state awareness** — an action's legality depends on the target resource's current state, not only the requester's identity.
8. **Data-classification awareness** — sensitivity of the data itself is an independent input, not implied by role alone.
9. **Time-bound authority** — every assignment has a validity window; expiry is enforced, not advisory.
10. **Device-aware restrictions** — some actions require a specific trusted device, evaluated independently of the operator's own authentication.
11. **No privilege inheritance through public relationships** — being publicly visible (e.g., an athlete's name in a published result) confers no system access to anyone.
12. **No silent privilege escalation** — every grant of additional authority is an explicit, auditable event, never an emergent side effect.
13. **Revocation must take effect predictably** — immediately for connected clients; within a bounded, disclosed lag for offline clients (see [offline-authorization-model.md](offline-authorization-model.md)).
14. **Sensitive actions require reason capture** — corrections, rejections, holds, revocations, and overrides record why, not just what.
15. **High-integrity actions require enhanced audit** — Critical-risk permissions (per [permission-catalog.md](permission-catalog.md)) carry Critical audit-level requirements.
16. **AI cannot exceed user authority** — an AI-assisted feature's effective access is the intersection of its own scope and the requesting user's authority, never a union (see Section 29).
17. **Offline authority is limited and revocable** — never a permanent, unbounded grant merely because a device is disconnected.
18. **Public visibility is publication-controlled** — the public sees only what an authoritative context has explicitly approved for release (see [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary)).
19. **Support access must be exceptional and traceable** — impersonation, where it exists at all, is disabled by default, reasoned, time-limited, and fully audited (Section 32).

## 5. Identity Model Summary

PMMS distinguishes **Person**, **Participant Profile**, **User Account**, **Organization Identity**, **Device Identity**, **Service Identity**, **API Client Identity**, **Public Anonymous Identity**, **Support Operator Identity**, **System Process Identity**, and **AI Assistant Execution Identity** as related but never conflated concepts — most importantly, a Person may exist with no User Account (the normal case for a minor athlete), and registering a Participant never automatically creates login access. Full definitions, relationship diagram, and lifecycle: [identity-model.md](identity-model.md).

## 6. Role Model

53 roles across 12 categories (Platform, Organization, Meet Governance, Registration, Eligibility, Competition, Technical Officiating, Results, Tally, Publication, Committee, Delegation, Public/Self-Service) are cataloged in [role-catalog.md](role-catalog.md), each marked Recommended / Requires validation / Optional / Deferred. Several candidate roles were **deliberately consolidated** rather than accepted as separate roles — most notably the five sport-officiating functions (Referee, Judge, Umpire, Scorer, Timer) into one Technical Official role activated with function-specific assignment metadata, and Assistant Coach/Team Manager/Coach Portal User into the single Coach role — per the Role Design Rules (Section 7) and working rule against creating a role explosion. See [role-catalog.md, Summary Table](role-catalog.md#summary-table) for the complete list with validation status, and note that six roles carry **blocking** status pending Phase 0.1 policy decisions (Eligibility Approver, Technical Delegate, Result Certifier, Tally Certifier).

## 7. Role Design Rules

- Roles describe reusable responsibilities, never organization IDs or meet IDs.
- Roles are not created per person or per meet.
- Roles do not replace assignments — a role with no assignment grants nothing.
- Committee membership alone does not imply full committee access; the specific position (Head/Staff/Viewer) within the committee assignment determines the permission subset (see [role-catalog.md, ROLE-36/37](role-catalog.md#role-36--committee-head--role-37--committee-staff)).
- Technical designations (officiating function, e.g., Referee vs. Timer) may be expressed as assignment metadata rather than separate roles, where the underlying permission set is genuinely the same shape (see the Technical Official consolidation above; flagged for revalidation as AD-05 once real per-sport structures are confirmed).
- A user may hold several roles simultaneously (see Section 14).
- A role may be globally defined but activated only through assignment.
- Platform roles (ROLE-01 through ROLE-05) must remain rare.
- Sensitive roles (Eligibility Approver, Result Certifier, Tally Certifier, Accreditation Officer, Medical Officer) require approval and periodic review (see [access-review-and-revocation.md](access-review-and-revocation.md)).
- Role names are business-readable, never technical/internal jargon.
- Permission bundles remain inspectable — no role's effective permission set should require reverse-engineering to understand.
- Composite roles never obscure a sensitive capability inside an innocuous-sounding bundle.
- Deprecated roles require a defined migration path for any user still holding them (a Phase 0.4+ implementation concern, flagged here as a requirement).

## 8. Permission Model

Permissions are grouped by bounded context (Administration, Meet Lifecycle, Committee Operations, Registration, Eligibility, Competition Entries, Tournament Management, Technical Officials, Scoring and Results, Protest and Appeals, Medal Tally, Accreditation and Access, Medical, Logistics, Finance, Public Information, Records and Reporting) with ~115 catalogued permissions covering high-value and high-risk operations — not an exhaustive field-level enumeration, which is a later-implementation concern. Every permission entry documents its business meaning, owning bounded context, risk level, required role candidates, required scope, required assignment, resource-state conditions, separation-of-duties considerations, audit level, and offline allowance. Full catalog: [permission-catalog.md](permission-catalog.md).

## 9. Permission Naming Standard

`resource.action`, lowercase, kebab-case for compound names (e.g., `official-result.certify`, `athlete-registration.review`). Rules: use stable business terminology from [domain-glossary.md](domain-glossary.md), never UI labels or route names; no generic `manage-all` or wildcard permissions for ordinary roles; `view-sensitive`/`view-restricted` always separate from `view`/`view-summary`; `approve`, `certify`, `publish`, `override`, `reopen`, `revoke` are always distinct permissions, never folded into a generic `edit`. Applied consistently across all ~115 catalogued permissions in [permission-catalog.md](permission-catalog.md).

## 10. Scope Architecture

Two largely independent hierarchies — Platform→Organization→Region→Division→District→School, and Platform→Meet→{Committee, Delegation, Sport→Event, Venue→Competition Area, Tournament→Match, Shift} — plus non-hierarchical dimensions (Record Ownership, Data Classification, Time, Device). **The two hierarchies do not automatically connect**: a Meet's Host Organization reference is a data relationship, not a scope-inheritance path. Full type definitions, composition rules, and a Mermaid diagram: [scope-model.md](scope-model.md).

## 11. Scope Evaluation Rules

Explicitly documented as a table of "does this inherit?" questions in [scope-model.md, Section 4](scope-model.md#4-scope-inheritance-rules) — the answer is **no** in every case listed (Platform scope does not bypass data classification; Organization scope does not grant meet authority; Meet scope does not grant all-sport access; Committee scope does not cross to other committees; Sport scope does not include result certification; Venue scope does not grant event-level competition authority; Delegation scope never allows modifying another delegation; record ownership never overrides approval separation; public scope permits only published projections). Multiple scopes are routinely required for one action (e.g., `official-result.certify` requires Meet ∩ Sport ∩ Event simultaneously). Scope expiry takes effect immediately for sensitive actions. Explicit restrictions always take precedence over inherited access.

## 12. Assignment Architecture

14 assignment types (Organizational, Meet, Committee, Delegation, Sport, Event, Venue, Tournament, Official, Shift, Device, Temporary Acting, Emergency, Support), each with subject, role, scope, validity window, approval, acceptance requirement, conflict declaration, reason, source-document reference, revocation authority, delegation restrictions, and audit requirements. Full model: [assignment-model.md](assignment-model.md).

## 13. Assignment Lifecycle

`Draft → Pending Approval → Pending Acceptance → Active → {Suspended, Expired, Revoked, Declined, Completed, Superseded}`, with lifecycle actions Propose, Approve, Accept, Activate, Suspend, Resume, Extend, Replace, Revoke, Complete, Supersede. Not every assignment type passes through every state — low-stakes assignments may activate directly; high-integrity assignments should always pass through explicit approval and acceptance. Full state diagram: [assignment-model.md, Section 4](assignment-model.md#4-assignment-lifecycle). **History is preserved permanently** for every assignment, even after reaching a terminal state.

## 14. Multiple and Concurrent Assignments

Holding multiple simultaneous assignments is the **normal** case, not an edge case — a user may be Tournament Manager for Basketball and Delegation Head for their own school in the same meet; may sit on more than one committee; may hold an Organization-level role and a Meet-level role concurrently; may be temporarily acting for another officer; may be assigned to multiple venues. **Concurrent assignment never automatically merges authority across scopes** — each assignment's scope is evaluated independently at request time. Schedule conflicts and separation-of-duties conflicts are both checked at assignment-creation time (see [assignment-model.md, Sections 5 and 9](assignment-model.md#5-multiple-and-concurrent-assignments)). Maximum concurrent high-integrity assignment limits are not fixed in Phase 0.3 — see AD-06 in [access-open-decisions.md](access-open-decisions.md).

## 15. Role Versus Assignment Examples

### Example 1
```text
Role: Tournament Manager
Assignment: Tournament Manager for Basketball, Provincial Meet 2027
Scope: Meet 2027 + Basketball
```

### Example 2
```text
Role: Eligibility Reviewer
Assignment: Eligibility Reviewer for Delegations A–D, Meet 2027
Scope: Meet 2027 + assigned delegations
```

### Example 3
```text
Role: Medical Staff
Assignment: Medical Team member at Venue 2, July 15–18
Scope: Meet + Venue 2 + assigned shift
```

### Example 4
```text
Role: Result Certifier
Assignment: Certifier for Athletics Track Events
Scope: Meet + Athletics + Track Events
```

### Example 5
```text
Role: Access Control Operator
Assignment: Gate Scanner Operator at Main Stadium Gate 1
Scope: Venue + gate + assigned device + shift
```

These five patterns anchor every role/scope discussion throughout this documentation package (see [role-catalog.md](role-catalog.md) and [assignment-model.md](assignment-model.md), which both cross-reference them by example number).

## 16. Effective Authorization Decision Model

An action may proceed only when all 16 steps of the decision sequence in [authorization-decision-model.md, Section 2](authorization-decision-model.md#2-decision-sequence) succeed, in order, with an early failure short-circuiting the rest: authentication, account status, no standing restriction, permission mapping, role qualification, assignment validity, scope match, resource-state check, data-classification check, separation-of-duties check, time validity, device trust, meet-status check, no explicit denial/security hold, approval-level check, audit logging. A 12-scenario decision table illustrating allow/deny outcomes across representative cases (score validation self-check, eligibility self-approval, offline scanning, meet closure with an open protest, impersonation without approval, and others) is in [authorization-decision-model.md, Section 5](authorization-decision-model.md#5-decision-table--representative-scenarios).

## 17. Policy Inputs

User ID, account status, identity assurance level, role, permission, assignment, assignment status, scope (organization/meet/committee/delegation/sport/event/venue/tournament unit), resource owner, resource status, data classification, time, device identity, network/offline state, conflict declaration, previous participation in the workflow, required approval level, security restrictions, emergency mode, explicit denial — enumerated fully in [authorization-decision-model.md, Section 1](authorization-decision-model.md#1-policy-inputs). No policy classes are defined; these are conceptual inputs for a later implementation phase.

## 18. Separation of Duties

11 conflict entries (SOD-01 through SOD-11) cover registration/eligibility, scoring/validation, result certification/protest resolution, tournament-manager-as-sole-protest-authority, tally encoding/certification, accreditation issuance/access override, finance encoding/approval, platform-administration/business-approval, user-administration/audit-review, medical-records/public-publication, device-administration/security-review, and support-impersonation/business-approval. **These are proposed architecture controls, not asserted DepEd policy** — each entry names a validation owner still to be identified. Full matrix: [separation-of-duties-matrix.md](separation-of-duties-matrix.md).

## 19. Approval Authority Levels

| Level | Meaning |
|---|---|
| Self-service | Acting on one's own record only (e.g., submitting one's own registration) |
| Operational | Routine execution within an assigned scope (e.g., recording a score) |
| Review | Evaluating another's submission without final authority (e.g., Eligibility Reviewer) |
| Approval | Rendering a decision with institutional weight (e.g., approving a budget allocation) |
| Certification | The highest institutional-trust tier for high-integrity domains (e.g., certifying a result) |
| Publication | Controlling what becomes externally visible, distinct from certification (per [domain-open-decisions.md, DD-17](domain-open-decisions.md#dd-17--public-publication-approval-chain)) |
| Override | Exceptional correction of an otherwise-blocked state (e.g., overriding a denied access scan) |
| Emergency | Time-boxed, narrowly-scoped authority under declared emergency conditions (Section 31) |

**A higher level does not automatically contain every lower-level permission unless explicitly designed that way** — a Result Certifier (Certification level) does not automatically gain Publication-level authority to publish that same result, per [domain-open-decisions.md, DD-17](domain-open-decisions.md#dd-17--public-publication-approval-chain)'s recommendation that certification and publication remain distinct steps.

## 20. High-Integrity Access Controls

Dedicated access-control treatment (sensitive operations, required roles/scopes/assignments, state conditions, separation-of-duties rules, approval level, audit level, correction/override control, offline limitation, AI limitation, open questions) for all 13 named high-integrity areas — Athlete Identity, Registration, Eligibility, Competition Entries, Tournament Progression, Scoring, Official Results, Protests, Medal Tally, Accreditation, Medical Records, Finance, and Audit — is in [high-integrity-access-controls.md](high-integrity-access-controls.md), which extends the Phase 0.2 domain-level safeguards in [high-integrity-domain-rules.md](high-integrity-domain-rules.md) with concrete role/scope/assignment gating.

## 21. Data Classification Model

| Classification | Examples |
|---|---|
| Public | Published schedules, published results, approved medal tally, public announcements, approved athlete profile display fields |
| Internal | Committee tasks, internal venue plans, operational readiness status |
| Confidential | User contact details, unpublished schedules, accreditation details, internal incidents |
| Restricted | Eligibility documents, guardian information, financial supporting records, protest evidence, access logs |
| Highly Restricted | Detailed medical records, authentication secrets, security investigation records, privileged audit exports, emergency access records |

**These classifications are proposed and require privacy and security validation** before being treated as final — see [access-open-decisions.md](access-open-decisions.md). Every permission in [permission-catalog.md](permission-catalog.md) that touches Restricted or Highly Restricted data is marked accordingly in its risk tier.

## 22. Public, Guest, and Self-Service Access

Anonymous public access, authenticated public accounts (Media User), athlete/coach/delegation self-service (via Coach and Delegation Head roles), and Parent/Guardian access (deferred, [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access)) are all bounded by: public users see only published projections from BC-29; self-service users access only their own or their delegation's permitted records; parents/guardians require a verified relationship (AD-02 in [access-open-decisions.md](access-open-decisions.md)); media access never grants protected athlete information; any future public API requires rate limits and publication controls; **no public account may modify official results**, full stop. See [identity-model.md, "Public Anonymous Identity"](identity-model.md#identity-categories) and [role-catalog.md, "Public and Self-Service Roles"](role-catalog.md#public-and-self-service-roles).

## 23. Organization and Meet Administration Boundaries

Platform Administrator, Organization Administrator, Regional/Division/School Administrator, Meet Administrator, and Committee Administrator are all **distinct, non-inheriting** authorities: Organization administration does not automatically grant scoring authority; Meet administration does not automatically grant medical access; Platform administration should not routinely view sensitive business records; Security Administration and business-approval authority remain distinct (SOD-07); technical-maintenance access uses exceptional, audited processes where feasible rather than routine administrative override. See [role-catalog.md, "Platform Roles"](role-catalog.md#platform-roles) and ["Organization Roles"](role-catalog.md#organization-roles).

## 24. Technical Official Authorization

Official identity lives in Participant Registry (BC-07, per [domain-open-decisions.md, DD-02](domain-open-decisions.md#dd-02--technical-official-identity-ownership)); qualification/competency references, meet/sport/event/venue assignment, acceptance, conflict declaration, and substitute/replacement process live in Technical Officials (BC-13). **Not all Technical Officials may score, validate, and certify** — these are three distinct permissions (`score-record.submit`, `score-record.validate`, `official-result.certify`) requiring three distinct assignments, enforced by SOD-02 and SOD-03. See [role-catalog.md, ROLE-27/28/29](role-catalog.md#role-27--technical-official) and [high-integrity-access-controls.md, "Scoring"](high-integrity-access-controls.md#scoring).

## 25. Committee Authorization

```text
Committee Membership
+ Committee Position
+ Assigned Role
+ Explicit Permissions
+ Meet Scope
+ Committee Scope
```

produces authority — committee membership alone grants nothing. Positions (Head, Deputy/Co-chair, Secretariat, Encoder, Reviewer, Approver, Viewer, Field staff, Temporary staff) determine which permission subset a committee assignment activates. Committee-specific business permissions (e.g., Medical's `medical-encounter.view-sensitive`) are activated through the specific committee assignment, never inferred from generic committee membership. See [role-catalog.md, "Operational Committee Roles"](role-catalog.md#operational-committee-roles) and [domain-open-decisions.md, DD-14](domain-open-decisions.md#dd-14--committee-specific-context-boundaries).

## 26. Delegation Authorization

Delegation Head, Coach (consolidating Assistant Coach/Team Manager), and — pending validation — School Coordinator and Parent/Guardian User. Restrictions: delegation users view only their own delegation; coaches manage only their assigned team/sport; athletes (where self-service exists at all — not at launch, per AD-03) view only their own records; delegation users submit but never approve eligibility (SOD-01); delegation users never certify official results (SOD-02/SOD-03); delegation changes lock after deadlines (`athlete-registration.lock`, `competition-entry.lock`); sensitive documents require limited access even within the delegation's own view. See [role-catalog.md, "Delegation Roles"](role-catalog.md#delegation-roles).

## 27. Device Identity

QR scanner stations, result encoding stations, scoreboard devices, kiosks, mobile official devices, accreditation printers, gate devices, and offline venue servers each carry: registration, name, type, owner, organization, meet/venue assignment, operational purpose, trust status, credential status, activation/revocation, last-seen, software version, offline capability, and audit behavior. Full model: [device-and-service-identity-model.md, Sections 1–4](device-and-service-identity-model.md#1-device-identity-categories).

## 28. Service and Machine Identities

Queue worker, scheduled job, integration client, public API client, mobile synchronization client, reporting service, AI service, notification service, import/export process — each requires narrowly scoped credentials, non-human identity records, no shared administrator credentials, revocability, auditability, expiry/rotation policies, and must never silently act as a human approver. Full model: [device-and-service-identity-model.md, Sections 5–6](device-and-service-identity-model.md#5-service-identity-categories).

## 29. AI Authorization Boundary

AI executes in the context of a requesting user or an approved service identity — never a free-standing identity with independent authority. AI cannot access data the user cannot access, and cannot infer additional authority. AI recommendations must identify their source context. AI must not approve eligibility, certify results, alter scores, resolve protests, award medals, issue medical decisions, or expose protected records. AI-generated drafts require human review. AI actions and data access require audit logging where they touch high-integrity or restricted data. External AI services require data-sharing approval (see [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions) and [domain-open-decisions.md, DD-26](domain-open-decisions.md#dd-26--ai-service-data-access-boundaries-domain-specific-framing)). See [device-and-service-identity-model.md, Section 7](device-and-service-identity-model.md#7-ai-service-identity-cross-reference) for the identity-model treatment.

## 30. Offline Authorization

Cached authorization snapshots are narrowly scoped, device-bound, and time-limited. Offline actions are classified **Provisional** (default, revalidated on sync) or **Final** (reserved for Low-risk, high-volume actions only). Never final offline: final eligibility approval, official result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, high-risk overrides. Potential offline-allowed (provisional) actions: score draft capture, access scan recording, medical incident recording, transport arrival recording, cached-data meal validation, venue incident recording. Revocation propagation to offline devices is prioritized above all other sync content but still carries a disclosed, bounded lag risk (RSK-08). Full model: [offline-authorization-model.md](offline-authorization-model.md).

## 31. Emergency and Break-Glass Access

A controlled, **not automatically assumed necessary**, concept requiring: explicit reason, limited duration, limited scope, strong identity verification, elevated audit logging, security notification, post-use review, automatic expiry, no hidden access, and restriction to genuinely approved emergency categories (medical/security) only. **Whether PMMS needs a formal break-glass mechanism at all is an open question with no recommended direction** — see AD-10 in [access-open-decisions.md](access-open-decisions.md), flagged as the single highest-priority unresolved question in this phase given the risk on both sides of the decision.

## 32. Impersonation and Support Access

Disabled by default. Limited to an explicitly authorized Support Administrator role (ROLE-04). Requires a reason or support ticket reference. User-visible indication where practical. Full audit trail. Time-limited session. No silent impersonation. Restricted access to Medical, Eligibility, Finance, and Security data even during an approved session. **Prohibited from approving or certifying as the impersonated user under any circumstance** (SOD-11, absolute — no exception). Session termination and mandatory post-session review. Whether this capability is needed at all for the initial release is open — see AD-09 in [access-open-decisions.md](access-open-decisions.md).

## 33. Access Grants, Restrictions, and Denials

Role grant, permission grant, scope grant, assignment grant, temporary grant, explicit denial, security restriction, data restriction, device restriction, time restriction. Precedence:

```text
Security Hold or Explicit Denial
overrides
Temporary Grant, Assignment, Role, or Inherited Scope
```

Sensitive permissions require explicit grant rather than inheritance from a broader role — see [authorization-decision-model.md, Sections 3–4](authorization-decision-model.md#3-explicit-deny-precedence).

## 34. Access Review and Recertification

Platform-role, organization-admin, meet-assignment, sensitive-role, medical-access, finance-access, support-access, dormant-account, device-credential, service-account, and post-meet access reviews, triggered by assignment end, meet closure, personnel transfer, committee replacement, security incident, privileged-role grant, long inactivity, device loss, account compromise, and organization offboarding. **No specific review interval is invented** — all are marked for policy validation (AD-20). Full treatment: [access-review-and-revocation.md, Part 1](access-review-and-revocation.md#part-1--access-review-and-recertification).

## 35. Revocation Model

Revocation of user accounts, roles, permission exceptions, assignments, devices, API clients, credentials, offline tokens, and support sessions. Immediate server-side revocation for connected clients; bounded, disclosed lag for offline clients; session and token invalidation; audit preservation (revocation never erases history); record-ownership continuity (past attribution survives revocation); assignment replacement for operational continuity; notification of affected administrators. Full model: [access-review-and-revocation.md, Part 2](access-review-and-revocation.md#part-2--revocation-model).

## 36. Authentication Architecture Boundaries

Without implementing authentication, PMMS's architecture requires readiness for: password authentication, MFA, email verification, account recovery, session controls, device recognition, risk-based authentication, SSO readiness, government/organization identity integration readiness, mobile token handling, API token handling, and a clear **distinction between QR accreditation credentials and login credentials** (they are never the same mechanism — a QR credential proves accreditation status at a scan point; a login credential authenticates a User Account, per [identity-model.md](identity-model.md)). The repository's existing Laravel Fortify-based scaffolding (password, 2FA, passkeys) is the confirmed starting foundation — this phase does not select a *different* mechanism, only confirms the architectural requirements it must satisfy (see AD-21 in [access-open-decisions.md](access-open-decisions.md)).

## 37. Authorization Testing Strategy

Future test categories (not implemented in this phase): permission tests, role composition tests, scope tests, assignment validity tests, cross-meet isolation tests, cross-organization isolation tests, committee isolation tests, delegation isolation tests, sport/event scope tests, resource-state tests, separation-of-duties tests, sensitive-data tests, public access tests, offline authorization tests, device identity tests, revocation tests, emergency access tests, AI boundary tests, privilege-escalation tests. Each maps directly to a section of this documentation package (e.g., "cross-meet isolation tests" verify [scope-model.md, Section 8](scope-model.md#8-cross-meet-isolation); "separation-of-duties tests" verify every entry in [separation-of-duties-matrix.md](separation-of-duties-matrix.md)) so that, once PestPHP tests are written in a later phase, traceability back to the architectural requirement is direct.

## 38. Authorization Risks and Anti-Patterns

| Anti-Pattern | Status in This Documentation Package |
|---|---|
| Treating roles as assignments | **Avoided** — explicit Role/Assignment distinction throughout (Sections 6, 12) |
| Creating roles per meet or per person | **Avoided** — roles are reusable across meets; assignments carry the meet-specific binding |
| Global administrator overuse | **Mitigated** — Platform roles (ROLE-01/02) required to remain rare per Role Design Rules |
| One `manage` permission for sensitive domains | **Avoided** — [permission-catalog.md](permission-catalog.md) separates approve/certify/publish/override/revoke throughout |
| Using frontend hiding as authorization | **Prevented architecturally** — the decision model (Section 16) is server-side by design; UI hiding is cosmetic only, never the enforcement mechanism |
| Direct role checks scattered across controllers | **Flagged for Phase 0.4** — centralized policy evaluation is a named architecture goal (Section 3), to be enforced in implementation |
| No resource-state checks | **Avoided** — Step 8 of the decision sequence is mandatory |
| No scope validation | **Avoided** — Step 7 of the decision sequence is mandatory |
| Medical access granted through committee membership alone | **Prevented** — Committee Authorization formula (Section 25) requires an explicit position/permission, not bare membership |
| Tournament manager automatically certifying results | **Explicitly prevented** — named anti-pattern in [role-catalog.md, ROLE-24/25](role-catalog.md#role-24--tournament-manager--role-25--assistant-tournament-manager) |
| Users approving their own work | **Prevented** — SOD-01 through SOD-06 |
| Offline devices retaining expired authority | **Bounded** — snapshot self-describing expiry per [offline-authorization-model.md, Section 13](offline-authorization-model.md#13-expired-authorization-while-offline) |
| Shared scanner credentials | **Flagged** — device ≠ operator principle in [device-and-service-identity-model.md, Section 3](device-and-service-identity-model.md#3-device-trust-principles) |
| Shared technical-official accounts | **Prevented** — every assignment binds to a specific User Account, never a shared login |
| Public portal querying protected operational data | **Structurally prevented at the domain layer** — inherited from [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary) |
| Support impersonation without audit | **Prevented** — SOD-11, Section 32 |
| AI services using unrestricted database credentials | **Prevented** — AI Service Identity narrow-scoping requirement (Section 28–29) |
| Deleting assignment history | **Prevented** — history preservation is mandatory (Section 13) |
| Permanent temporary access | **Prevented** — every temporary/emergency/acting assignment is time-boxed by definition |
| Wildcard permissions | **Prevented** — naming standard explicitly forbids `manage-all` (Section 9) |
| Permission names tied to UI buttons | **Prevented** — naming standard requires stable business terminology (Section 9) |
| Cross-context permissions without ownership | **Prevented** — every permission is attributed to one owning bounded context in [permission-catalog.md](permission-catalog.md) |
| Security administrators acting as business approvers | **Prevented** — SOD-07 |

## 39. Recommended Architecture Direction

> PMMS should use a hybrid authorization model combining RBAC, scoped assignments, resource attributes, workflow state, data classification, and explicit separation-of-duties controls.

The initial implementation may use a Laravel-compatible role and permission foundation (the repository already contains Laravel 13 with Fortify scaffolding), but **domain authorization must be enforced through application policies and centralized decision rules rather than package-level role checks alone** — a package like Spatie Permission, if adopted in a later phase, would provide the role/permission *storage and query* mechanism, not the *scope-aware, assignment-validated, separation-of-duties-enforcing decision logic* this document requires, which must be built as an explicit policy layer on top. **No package is installed or configured during this phase.**

## 40. Phase 0.3 Deliverables

1. [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) (this document)
2. [identity-model.md](identity-model.md)
3. [role-catalog.md](role-catalog.md)
4. [permission-catalog.md](permission-catalog.md)
5. [scope-model.md](scope-model.md)
6. [assignment-model.md](assignment-model.md)
7. [authorization-decision-model.md](authorization-decision-model.md)
8. [separation-of-duties-matrix.md](separation-of-duties-matrix.md)
9. [high-integrity-access-controls.md](high-integrity-access-controls.md)
10. [device-and-service-identity-model.md](device-and-service-identity-model.md)
11. [offline-authorization-model.md](offline-authorization-model.md)
12. [access-review-and-revocation.md](access-review-and-revocation.md)
13. [access-open-decisions.md](access-open-decisions.md)
14. [README.md](README.md) (updated architecture documentation index)
15. [../../.ai/project-context.md](../../.ai/project-context.md) (updated)
16. [../../.ai/current-phase.md](../../.ai/current-phase.md) (updated)
17. [../../.ai/architecture.md](../../.ai/architecture.md) (updated)
18. [../../.ai/security-rules.md](../../.ai/security-rules.md) (new)
19. [../../.ai/authorization-rules.md](../../.ai/authorization-rules.md) (new)
20. [../../.ai/decisions/ADR-0003-role-permission-scope-and-assignment-architecture.md](../../.ai/decisions/ADR-0003-role-permission-scope-and-assignment-architecture.md) (new)

## 41. Phase 0.3 Acceptance Criteria

- [x] Person and User Account are distinct concepts.
- [x] Participant identity and login identity are distinct.
- [x] Role and Assignment are distinct concepts throughout.
- [x] Permissions represent business actions (certify, publish, approve, revoke, resolve, assign, validate, reopen, supersede, close), not only CRUD.
- [x] Scope types are defined (18 types across two hierarchies plus non-hierarchical dimensions).
- [x] Assignment types are defined (14 types).
- [x] Assignment lifecycle is documented (10 states, 11 actions).
- [x] Multiple concurrent assignments are addressed.
- [x] Effective authorization decision model is documented (16-step sequence, 12-scenario decision table).
- [x] Permission naming standard is defined and applied.
- [x] Role catalog exists (53 roles).
- [x] Permission catalog exists (~115 permissions across 17 categories).
- [x] Scope model exists.
- [x] Assignment model exists.
- [x] Separation-of-duties matrix exists (11 entries).
- [x] High-integrity access controls are documented (13 domains).
- [x] Data classifications are proposed (5 tiers).
- [x] Public and self-service access boundaries are documented.
- [x] Device identities are documented (8 categories).
- [x] Service identities are documented (10 categories).
- [x] Offline authorization is documented.
- [x] AI authorization boundaries are documented.
- [x] Emergency access is documented (with an explicit "no recommended direction" flag where genuinely undecided).
- [x] Support impersonation is documented.
- [x] Access review and revocation are documented.
- [x] Authorization test strategy is documented (categories, not implementations).
- [x] Open decisions are recorded (22 access-specific decisions, cross-referenced against Phase 0.1/0.2 decisions).
- [x] AI workspace is updated.
- [x] No implementation code is generated.
- [x] No migrations are created.
- [x] No package is installed.
- [x] No official authority rule is invented (every role/authority question traces to a Phase 0.1/0.2/0.3 open decision requiring DepEd/security validation).
- [x] Documents are internally consistent.

## 42. Exit Criteria

Phase 0.3 is complete because:

- PMMS has a clear conceptual identity model distinguishing Person, Participant, and User Account.
- Roles no longer imply unlimited authority — every sensitive role requires a validated Assignment within an explicit Scope.
- Sensitive actions have explicit permissions, never bundled into generic CRUD or `manage-all`.
- Operational authority is assignment- and scope-aware throughout.
- High-integrity workflows include separation-of-duties safeguards (11 SOD entries covering every high-integrity domain named in Phase 0.2).
- Public, mobile, device, service, and offline access boundaries are understood and documented.
- Access review and revocation principles are documented.
- **Phase 0.4 can design the application, integration, and infrastructure architecture using the approved authorization model** — every bounded context from Phase 0.2 now has a corresponding set of roles, permissions, and scopes it can implement policies against.
- No implementation was prematurely generated — verified in the completion report's quality checks.

## 43. Next Phase

```text
Phase 0.4 — Application, Integration, and Runtime Architecture
```

Phase 0.4 is not started as part of this task.
