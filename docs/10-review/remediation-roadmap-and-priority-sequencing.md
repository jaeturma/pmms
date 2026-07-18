# PMMS Remediation Roadmap and Priority Sequencing

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [architecture-gap-register.md](architecture-gap-register.md), [implementation-readiness-assessment.md](implementation-readiness-assessment.md)

**This document is not the Phase 1 backlog** — restated absolutely per the working rule governing this phase. It sequences *decisions and remediation items*, not implementation work packages, which remain Phase 0.14's responsibility.

---

## Priority 0 — Phase 0 Closure Blockers

Must be resolved before Phase 0 is considered complete.

| Item | Type |
|---|---|
| GAP-13 — Reviewer/document-owner roles unassigned | Governance |
| DR-09 / GAP-12 — Participant identity modeling (DD-01) | Decision |

## Priority 1 — Phase 1 Foundation Blockers

Must be resolved before core foundation implementation begins.

| Item | Type |
|---|---|
| GAP-01 — Physical database schema (first Phase 0.14 work package) | Architecture |
| DR-11 / GAP-04 — Deployment topology (DV-01) | Decision (deployment planning only, does not block application code) |
| GAP-07 — Retention periods (PD-04), blocked on PSG-03 | Decision + policy source |
| DR-14 / GAP-09 — WCAG conformance target (DX-01) | Decision |
| GAP-16 — CI/CD pipeline | Implementation |

## Priority 2 — Module Implementation Dependencies

Resolve before the affected module begins.

| Item | Affected Module |
|---|---|
| DR-13 / GAP-06 — Outbox pattern (WD-08) | Scoring, Official Results, Accreditation |
| DR-01 (OD-07), PSG-04 | Eligibility |
| DR-02 (OD-08) | Official Results |
| DR-03 (OD-09), PSG-15 | Protest and Appeals |
| DR-04 (OD-10), PSG-04/14 | Sports Catalog, Competition Entries (per sport) |
| DR-05 (OD-12), PSG-16 | Medal Tally |
| DR-06 (OD-15), PSG-05 | Medical Operations |
| PSG-06 | Finance Operations |
| GAP-09 (DX-02, branding) | UI components (non-blocking for functional work) |

## Priority 3 — Pilot Dependencies

Resolve before pilot.

| Item | Type |
|---|---|
| GAP-08 — UX proto-persona validation | Evidence |
| GAP-03 — First controlled pilot itself (QD-13) | Evidence-generating event |
| ARR-08 / TD-09 — First restore drill | Evidence |
| DR-08 (OD-03, single-vs-multi-meet) | Decision |

## Priority 4 — Production Dependencies

Resolve before production.

| Item | Type |
|---|---|
| GAP-05 / DR-12 — RPO/RTO numeric targets (ED-33) | Decision, evidence-gated |
| GAP-11 — Break-glass/impersonation necessity (AD-09/AD-10) | Decision |
| Full security/privacy/accessibility review cycle | Evidence |
| Operational runbooks | Documentation/Implementation |
| Vendor review (once any vendor is selected) | Evidence |

## Priority 5 — Enterprise or Commercial Enhancements

May be deferred indefinitely, pending real demand.

| Item | Type |
|---|---|
| All 13 AI capabilities (AX-01/02/03) | Deferred capability |
| Multi-tenancy activation (OD-02, ED-05/06) | Deferred capability |
| SSO/enterprise identity | Deferred capability |
| Disaster-recovery infrastructure (multi-region, warm standby) | Deferred capability |
| Licensing/billing platform (OD-22) | Deferred capability |
| GAP-14, GAP-15 — Documentation-consistency items | Documentation |

## Recommended Sequencing Narrative

1. **Immediately (P0):** Name reviewer roles; resolve DD-01 (participant identity) in a focused architect working session — this single decision unblocks the schema-design work package.
2. **Phase 0.14 Week 1 (P1):** Physical schema design as the first work package; begin deployment-topology and CI/CD decisions in parallel (they do not block schema design).
3. **Phase 0.14 Foundation (P1–P2):** Implement Identity/Access, Organization Directory, Meet Administration, Audit, Document/Records, Configuration/Reference Data — none blocked by any open policy decision.
4. **Parallel track, ongoing (P2):** Pursue the DepEd/sports-governing-body engagement for OD-07/08/09/10/12/15 and their PSG-04/05/06/14/15/16 source gaps — this is the single highest-leverage parallel activity, since it unblocks six otherwise-stalled modules simultaneously.
5. **As policy decisions resolve (P2):** Implement Eligibility, Scoring/Official Results (after the outbox spike), Protest and Appeals, Medal Tally, Medical Operations, Finance — in whatever order their respective policy sources actually resolve, not a fixed sequence.
6. **Pilot preparation (P3):** Validate proto-personas, select pilot meet/sports/committees, generate test data, run the first restore drill.
7. **Post-pilot (P4):** Set RPO/RTO from real evidence, complete security/privacy/accessibility review cycles, prepare production runbooks.
8. **Only once genuine demand exists (P5):** Activate AI, multi-tenancy, SSO, or DR-infrastructure investment.

## Avoiding the Compounding-Bottleneck Risk (ARR-09)

**Do not resolve Priority 0–2 items sequentially, one at a time, as they happen to surface.** Convene a single, focused working session addressing DD-01, DV-01, the OD-07/08/09/10/12/15 cluster, and DX-01 together — since several share the same DepEd/sports-governing-body stakeholders, a combined engagement is materially faster than six separate ones.

## Explicitly Not Blocking Phase 1

Restated absolutely, per working rule 45: AI (all 13 capabilities), multi-tenancy activation, SSO, disaster-recovery infrastructure beyond basic backup, database sharding, Kubernetes, and billing/licensing platforms **never block Phase 1 foundation or initial meet-operations implementation** — every one of these is Priority 5, correctly and deliberately deferred by the architecture itself, not merely by this roadmap.
