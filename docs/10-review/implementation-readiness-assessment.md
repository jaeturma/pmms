# PMMS Implementation Readiness Assessment

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [architecture-gap-register.md](architecture-gap-register.md), [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md)

For each major architecture capability: readiness status, dependencies, blockers, constraints, evidence, recommended target work package, required reviewer, notes. **No work package is created here** — this assessment informs, but does not perform, Phase 0.14's decomposition.

---

| Capability | Readiness Status | Dependencies | Blockers | Constraints | Evidence Level | Recommended Target Work Package | Required Reviewer |
|---|---|---|---|---|---|---|---|
| Physical database schema | Requires Decision Before Backlog | DD-01 (participant identity) | GAP-01, GAP-12 | Must precede every other data-touching work package | Documented (logical only) | Phase 0.14, Work Package 1 | Lead architect, Data owner |
| Identity/Access module (BC-02) | Ready with Constraints | Physical schema | None architectural | Must implement full authority formula (role+permission+scope+assignment), never role-only shortcut | Documented, Cross-Validated | Phase 0.14, early foundation | Security reviewer |
| Organization Directory (BC-03) | Ready with Constraints | Physical schema | DD-09 (data source) open, non-blocking | Tenant-key-ready from first migration | Documented, Cross-Validated | Phase 0.14, foundation | Domain reviewer |
| Meet Administration (BC-04) | Ready with Constraints | Physical schema, Identity/Access | OD-03 (single-vs-multi-meet) | Meet-lifecycle states per WF-01 | Documented, Cross-Validated | Phase 0.14, foundation | Domain reviewer |
| Athlete/Delegation Registration (BC-06, BC-08) | Ready with Constraints | Meet Administration | DD-01 (participant identity) | — | Documented, Cross-Validated | Phase 0.14, initial meet operations | Domain reviewer |
| Eligibility and Clearance (BC-09) | **Requires Policy Validation** | Registration | OD-07, PSG-04 | SOD-01 enforcement pending OD-07 | Documented only | Deferred until OD-07/PSG-04 resolved | Domain reviewer, Eligibility Authority |
| Sports Catalog / Competition Entries (BC-10, BC-11) | **Requires Policy Validation** | Registration | OD-10, PSG-04/14 | Per-sport, staged rollout | Documented only | Deferred, sport-by-sport | Sports-domain representative |
| Tournament Management (BC-12) | Ready with Constraints | Sports Catalog | DD-13 (sport-plugin strategy) | Format-configuration extension point, not hard-coded | Documented, Cross-Validated | Phase 0.14, after Sports Catalog | Domain reviewer |
| Scoring (BC-15) | **Requires Technical Spike** | Tournament Management | WD-08 (outbox pattern) | Synchronous validation required; offline-critical | Documented, Cross-Validated | Phase 0.14, after outbox spike | Lead architect |
| Official Results (BC-16) | **Requires Policy Validation + Technical Spike** | Scoring | OD-08, WD-08 | Certification/publication separation strictly enforced | Documented only | Deferred until OD-08 resolved, contingent on outbox spike | Domain reviewer |
| Protest and Appeals (BC-17) | **Requires Policy Validation** | Official Results | OD-09, PSG-15 | SOD-03 enforcement pending OD-09 | Documented only | Deferred until OD-09/PSG-15 resolved | Domain reviewer |
| Medal Tally (BC-18) | **Requires Policy Validation** | Official Results | OD-12, PSG-16 | SOD-04 enforcement pending OD-12 | Documented only | Deferred until OD-12/PSG-16 resolved | Domain reviewer |
| Accreditation / Access Validation (BC-19, BC-20) | Ready with Constraints | Registration, Eligibility (readiness gate) | None architectural | Offline-critical, revocation-priority sync | Documented, Cross-Validated | Phase 0.14, pilot enhancements | Security reviewer |
| Medical Operations (BC-21) | **Requires Policy Validation** | — | OD-15, PSG-05 | Strongest privacy boundary; SOD-09 pending | Documented only | Deferred until OD-15/PSG-05 resolved | Privacy reviewer, Medical Team lead |
| Logistics (BC-22–24) | Ready for Backlog Decomposition | Meet Administration | None | Moderate-risk tier | Documented, Cross-Validated | Phase 0.14, pilot enhancements | Domain reviewer |
| Security Operations (BC-25) | Ready for Backlog Decomposition | Access Validation | None | — | Documented, Cross-Validated | Phase 0.14, pilot enhancements | Security reviewer |
| Finance Operations (BC-26) | **Requires Policy Validation** | Meet Administration | PSG-06 | SOD-06 enforcement | Documented only | Deferred until PSG-06 resolved | Domain reviewer |
| ICT Service Operations (BC-27) | Ready for Backlog Decomposition | — | None | — | Documented, Cross-Validated | Phase 0.14, pilot enhancements | Domain reviewer |
| Media and Communications / Public Information (BC-28, BC-29) | Ready with Constraints | Official Results | PSG-12 (non-blocking, moderate) | Public projections only, never operational tables | Documented, Cross-Validated | Phase 0.14, initial meet operations | UX reviewer |
| Document and Records (BC-30) | Ready for Backlog Decomposition | Physical schema | None | 15-stage upload lifecycle | Documented, Cross-Validated | Phase 0.14, foundation | Security reviewer |
| Notifications (BC-31) | Ready with Constraints | Every notification-triggering context | Provider selection (SMS/push deferred) | In-app/email first; SMS/push explicitly deferred | Documented, Cross-Validated | Phase 0.14, pilot enhancements | Domain reviewer |
| Audit and Compliance (BC-32) | Ready for Backlog Decomposition | Physical schema | None | Append-only, no correction authority | Documented, Cross-Validated | Phase 0.14, foundation (early) | Security reviewer |
| Reporting and Analytics (BC-33) | Ready with Constraints | Every source context | None architectural | Read-only, non-authoritative | Documented, Cross-Validated | Phase 0.14, pilot enhancements | Domain reviewer |
| Configuration and Reference Data (BC-34) | Ready for Backlog Decomposition | Physical schema | DD-22 (scope) | Never absorbs business rules | Documented, Cross-Validated | Phase 0.14, foundation | Domain reviewer |
| Mobile (Flutter) | Deferred | Core web foundation | `mobile/` does not exist | Offline-first, Access Validation/Scoring priority | Documented only | Post-foundation, pilot enhancements | Engineering lead |
| AI capabilities (all 13) | Deferred | Everything else | AX-01/02/03 | None active | Documented only | Post-pilot | AI governance owner |
| Multi-tenancy activation | Deferred | OD-02 resolution | ED-05/06 | Stage 4+ only | Documented only | Post-pilot, contingent on OD-02 | Product owner |
| Workflow automation entries | Deferred | Corresponding workflow implementation | Per-entry Automation Authority Model review | None enabled | Documented only | Post-foundation | Workflow governance owner |
| SSO/Enterprise identity | Deferred | — | Stage 5 candidate | — | Documented only | Post-pilot | Security reviewer |
| Disaster recovery infrastructure | Deferred | DV-01 | ED-33/34 | — | Documented only | Post-pilot, Stage 2+ | Infrastructure owner |

## Summary

19 of 30 capabilities are **Ready for Backlog Decomposition** or **Ready with Constraints** — sufficient for a manageable Phase 1 foundation and initial meet operations. Six capabilities (**Eligibility, Sports Catalog/Entries, Official Results, Protest and Appeals, Medal Tally, Medical Operations, Finance**) **Require Policy Validation** before implementation — all trace to the same small cluster of Phase 0.1 open decisions (OD-07/08/09/10/12/15) and policy-source gaps (PSG-04/05/06/14/15/16). Five categories (Mobile, AI, multi-tenancy, workflow automation, SSO, DR infrastructure) are correctly **Deferred**.

## Open Questions

See [remediation-roadmap-and-priority-sequencing.md](remediation-roadmap-and-priority-sequencing.md) for full sequencing.
