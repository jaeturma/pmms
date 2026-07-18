# PMMS Phase 1 Scope and Release Strategy

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-0.14-phase-1-implementation-backlog.md](phase-0.14-phase-1-implementation-backlog.md)

## 1. Phase 1 Goal

Phase 1 will establish a verified, secure, testable, modular, observable, tenant-ready, and cross-platform engineering foundation upon which PMMS sports, committee, public, mobile, accreditation, scoring, analytics, and AI capabilities can be safely implemented in later phases.

## 2. Included Scope

The 40 foundation items enumerated in the Phase 0.14 prompt, realized across 15 epics — see [phase-1-epic-catalog.md](phase-1-epic-catalog.md). In short: engineering/tooling verification, the modular-monolith skeleton, identity/authentication/session, organization/meet context with tenant-ready conventions, the full authorization model, audit/activity-history/security-event capability, reference data/configuration, file/object storage, queue/scheduler/notification/Reverb runtime, API/Inertia/frontend conventions, the web design system, the Flutter foundation, observability, cross-cutting security/privacy safeguards, and foundation integration/sign-off.

## 3. Excluded Scope

Athlete/delegation registration, eligibility adjudication, sport-specific scoring, tournament brackets, protest resolution, medal tally, QR accreditation operational rollout, medical case management, finance workflows, general internal messaging, all AI capabilities, billing/subscription/licensing, advanced multi-tenancy activation, SSO, database sharding, read replicas, Kubernetes, multi-region deployment, data warehouse, dedicated search engine, full Flutter operational modules, and public portal feature completeness — see [phase-1-deferred-scope.md](phase-1-deferred-scope.md) for the full register with rationale and target phase per item.

## 4. Release Groups

| Group | Epics | Theme |
|---|---|---|
| Foundation Release A | EPIC-01, EPIC-02 | Engineering and architecture baseline |
| Foundation Release B | EPIC-03, EPIC-04, EPIC-05 | Identity, context, and authorization |
| Foundation Release C | EPIC-06, EPIC-07, EPIC-08 | Audit, data, files, and configuration |
| Foundation Release D | EPIC-09, EPIC-10, EPIC-11 | Runtime, APIs, and web experience |
| Foundation Release E | EPIC-12, EPIC-13, EPIC-14 | Mobile, operations, and security |
| Foundation Release F | EPIC-15 | Integration and foundation sign-off |

Release groups are sequential at the group level (A must substantially complete before D depends on it; see dependency map); within a group, epics proceed largely in parallel once hard predecessors clear.

## 5. Critical Path

See [phase-0.14-phase-1-implementation-backlog.md, Section 14](phase-0.14-phase-1-implementation-backlog.md#14-critical-path) and [phase-1-execution-sequence.md](phase-1-execution-sequence.md).

## 6. Parallel Work

Once WP-01-01 and WP-02-01/WP-02-02 complete: EPIC-11 (design tokens/theme), EPIC-12 (Flutter project baseline), and EPIC-13 (logging/correlation) may proceed in parallel with EPIC-03 through EPIC-09. Within Release B, EPIC-03 (identity) and the early parts of EPIC-04 (organization hierarchy) may proceed in parallel; EPIC-05 (authorization) requires both to reach WP-04-03 (membership) before its scope model can bind real context.

## 7. Pilot Relevance

Foundation work packages flagged "Required" for pilot readiness in [phase-1-work-package-catalog.md](phase-1-work-package-catalog.md) are the minimum foundation needed before [../10-review/pilot-production-and-enterprise-readiness-assessment.md](../10-review/pilot-production-and-enterprise-readiness-assessment.md)'s pilot-readiness prerequisites can be re-assessed as met. Sport-module pilot readiness remains separately gated on the OD-07/08/09/10/12/15 policy cluster, unaffected by Phase 1 completion.

## 8. Completion Approach

Phase 1 completion is assessed by EPIC-15 (Foundation Integration, Validation, and Release Readiness), culminating in WP-15-12 (Phase 1 Foundation Sign-Off Package). No release group is declared complete until its constituent work packages meet the global Definition of Done ([phase-1-definition-of-done.md](phase-1-definition-of-done.md)) and pass the applicable quality gates ([phase-1-quality-gates.md](phase-1-quality-gates.md)).

## 9. Risks

See [phase-1-risk-register.md](phase-1-risk-register.md) for the full register. Highest-relevance risks to sequencing: scope expansion into excluded sports modules (RISK-GENERAL-01), authorization-model rework if DD-01-adjacent decisions arrive mid-Phase-1 (RISK-GENERAL-03), and Flutter-workspace absence requiring a larger-than-expected WP-01-05/WP-12-01 effort (RISK-GENERAL-05).

## 10. Open Questions

- Should Foundation Release E (Flutter/Observability/Security) proceed in parallel with Release D, or strictly after, given limited engineering capacity? Recommended: parallel, since EPIC-12/13/14 share few hard dependencies with EPIC-09/10/11 beyond WP-01-01 and WP-02-02.
- Should WCAG conformance (DX-01) be provisionally fixed at 2.1 AA for Phase 1 to unblock WP-11-12/WP-12-12, pending formal confirmation? Recommended: yes, tracked as DEC-GENERAL-01 in [phase-1-decision-register.md](phase-1-decision-register.md).
