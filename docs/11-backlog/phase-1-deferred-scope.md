# PMMS Phase 1 Deferred Scope

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-1-scope-and-release-strategy.md](phase-1-scope-and-release-strategy.md)

Every capability excluded from Phase 1, with rationale and target phase. This is the authoritative register that [phase-0.14-phase-1-implementation-backlog.md, Section 5](phase-0.14-phase-1-implementation-backlog.md#5-phase-1-exclusions) summarizes.

| Deferred Item | Rationale | Blocking Decision(s) | Target Phase |
|---|---|---|---|
| Athlete/delegation registration (BC-06, BC-08) | Requires DD-01 (participant identity modeling) resolution first | DR-09/GAP-12 | Phase 2 (Initial Meet Operations), contingent on DD-01 |
| Eligibility adjudication (BC-09) | Requires Policy Validation | OD-07, PSG-04 | Phase 2, contingent on policy source |
| Sport-specific scoring and tournament brackets (BC-12, BC-15) | Requires Technical Spike (outbox) and verified sport sources | WD-08, OD-10, PSG-14 | Phase 2, contingent on spike + policy source |
| Official results certification/publication (BC-16) | Requires Policy Validation | OD-08 | Phase 2, contingent on policy source |
| Protest and appeal resolution (BC-17) | Requires Policy Validation | OD-09, PSG-15 | Phase 2, contingent on policy source |
| Medal tally (BC-18) | Requires Policy Validation | OD-12, PSG-16 | Phase 2, contingent on policy source |
| QR accreditation operational rollout (BC-19, BC-20) | Pilot Enhancement per Phase 0.13 MVP boundary; foundation pieces (identity, files, queue) built in Phase 1 | None architectural | Pilot Enhancements phase |
| Medical case management (BC-21) | Requires Policy Validation; strongest privacy boundary | OD-15, PSG-05 | Phase 2, contingent on policy source |
| Finance workflows (BC-26) | Requires Policy Validation | PSG-06 | Phase 2, contingent on policy source |
| General internal messaging | Beyond notification foundation; WD-XX open (whether built at all) | Workflow open decision | Deferred, contingent on demand |
| All 13 AI capabilities | Advisory-only and deferred per ADR-0010/ADR-0013 | AX-01/02/03 | Post-pilot |
| Billing, subscription plans, commercial licensing | Enterprise Maturity Stage 1; no commercial decision made | OD-22 | Post-pilot, Stage 4+ |
| Advanced multi-tenancy activation | Enterprise Maturity Stage 1; tenant-ready conventions only in Phase 1 | OD-02, ED-05/06 | Post-pilot, Stage 4+ |
| SSO/enterprise identity | Stage 5 candidate | — | Post-pilot |
| Database sharding, read replicas | No measured scale justification | — | Post-pilot, evidence-gated |
| Kubernetes, multi-region deployment | No measured operational-capability justification | DV-01 (deployment topology, separate question) | Post-pilot, evidence-gated |
| Data warehouse, dedicated search engine | Deferred per DD-25/RD-08 until real reporting/search needs are known | — | Post-pilot |
| Full Flutter operational modules | Only the Flutter foundation/skeleton (EPIC-12) is built in Phase 1 | None architectural | Pilot Enhancements phase |
| Public portal feature completeness | Foundation conventions only; BC-28/29 public projections remain a later Initial-Meet-Operations item | PSG-12 (non-blocking) | Phase 2 |

## Deferral Discipline

Every row above is either contingent on a specific, named decision (in which case Phase 1 does not wait for it) or is a Priority 5 enterprise/AI item that the architecture itself — not merely this backlog — defers (per ADR-0012/ADR-0013's own confirmation). No deferred item is deferred merely for convenience; each has a stated rationale traceable to a Phase 0 or Phase 0.13 source.
