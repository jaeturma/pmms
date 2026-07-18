# PMMS Identity, Access, Scope, and Assignment Review

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../01-architecture/phase-0.3-access-and-assignment-architecture.md](../01-architecture/phase-0.3-access-and-assignment-architecture.md)

---

## 1. Identity Categories

Person, Participant Profile, User Account, Device Identity, Service Identity, and (as of Phase 0.10) AI Service Identity are consistently distinguished across every phase that touches identity — confirmed as never conflated in this review's contradiction analysis. **Assessment: Strong.**

## 2. Roles

53 roles across 12 stated categories, confirmed by direct tally against [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md). A minor documentation inconsistency (category-count labeling) is tracked in [architecture-consistency-and-contradiction-analysis.md, Section 9](architecture-consistency-and-contradiction-analysis.md#9-documentation-inconsistencies-found-distinct-from-architecture-contradictions) — the role set itself is not in question.

## 3. Permissions

Approximately 115 permissions (source's own qualifier; direct row-count is 104) across 17 categories, in `resource.action` kebab-case format, mapped one-to-one to bounded contexts. **Assessment: Strong**, with the count-precision item tracked as documentation debt.

## 4. Scope

18 scope types across two largely independent hierarchies (Organization tree; Meet tree), with explicit non-inheritance rules for sensitive actions (Organization scope does not grant Meet authority; Meet scope does not grant Sport authority). This non-inheritance discipline is the architecture's strongest authorization guarantee and is restated, never weakened, through Phase 0.9's frontend-hiding-is-never-authorization principle and Phase 0.12's tenant-context-as-additional-input model.

## 5. Assignment

Time-bound, scoped assignments activate roles — a role held with zero active assignment grants zero effective authority, restated consistently. Assignment lifecycle, multiple/concurrent assignments, and role-versus-assignment worked examples are all present in Phase 0.3's primary document.

## 6. Service Identities

Consistently and correctly restricted: no standing write credential (Phase 0.4), scoped per automation entry (Phase 0.11's Automation Authority Model), scoped via intersection-not-union (Phase 0.10's AI service identity), and never granted unrestricted tenant access (Phase 0.12). **Assessment: Strong — this is one of the architecture's most consistently reinforced boundaries across every subsequent phase that introduces a new non-human actor.**

## 7. Device Identities

Consistently distinguished from operator identity across Phase 0.3, 0.9 (shared-device/kiosk UX), and Phase 0.12 (device trust never substitutes for operator identity). **Assessment: Strong.**

## 8. Separation of Duties

11 entries plus one lettered sub-entry (SOD-01–SOD-11, SOD-03b), consistently restated and applied at the workflow-transition level in Phase 0.11 and the tenant-administration level in Phase 0.12. **Three of twelve entries remain formally blocking** on unresolved Phase 0.1 decisions:

| SOD | Blocked On | Status |
|---|---|---|
| SOD-01 (Registration/Eligibility) | OD-07 | Open — enforcement mechanism pending |
| SOD-03 (Result Certification/Protest) | OD-09 | **Blocking** |
| SOD-04 (Medal Tally) | OD-12 | **Blocking** |
| SOD-09 (Medical Records/Public Information) | OD-15 | **Blocking** |

The remaining eight entries (SOD-02, SOD-03b, SOD-05 through SOD-08, SOD-10, SOD-11) are "Open" or "Recommended," not policy-blocked — implementable once role/permission assignment begins.

## 9. Privileged Access

Break-glass and support impersonation remain genuinely undecided (AD-09, AD-10) — restated unchanged as "not a default capability to build toward" through every subsequent phase, never silently assumed as a requirement. **Assessment: Strong process discipline on a genuinely open question.**

## 10. Impersonation

SOD-11's absolute rule (an active impersonation session may never execute an approval/certification/publication action) is the single most consistently repeated authorization rule across the entire 12-phase corpus — restated verbatim or near-verbatim in Phase 0.3, 0.6, 0.9, 0.11, and 0.12. **Assessment: Strong.**

## 11. Tenant Membership

Phase 0.12 correctly extends, rather than redefines, the authority formula with a tenant-membership input — confirmed non-contradictory in this review's consistency analysis.

## 12. Missing Role-Permission Coverage

No systematic gap was found between the 53 roles and the ~115 permissions — every role catalog entry maps to at least one permission category matching its bounded-context scope. The one identified structural gap is that no role is yet catalogued for **cross-tenant auditor** (a Phase 0.12 candidate concept, tracked as ED-10 in [../09-enterprise/enterprise-open-decisions.md, "Multi-Tenancy and Data Isolation"](../09-enterprise/enterprise-open-decisions.md#multi-tenancy-and-data-isolation-ed-05--ed-16-ed-21)) — correctly deferred, since multi-tenancy itself remains unactivated.

## 13. Open Questions

AD-09/AD-10 (break-glass/impersonation necessity) and the SOD-01/03/04/09 policy-blocking chain (OD-07/09/12/15) remain the highest-priority identity/access decisions — see [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md).
