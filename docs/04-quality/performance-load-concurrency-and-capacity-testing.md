# PMMS Performance, Load, Concurrency, and Capacity Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/resilience-performance-and-scaling.md](../01-architecture/resilience-performance-and-scaling.md) · [../02-data/indexing-performance-and-capacity.md](../02-data/indexing-performance-and-capacity.md) · [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md)

This document defines PMMS's performance, load, stress, spike, soak, capacity, concurrency, and race-condition testing strategy. **No load-testing script, target number, or performance-testing tool is created or selected here** — every numeric target is explicitly a placeholder pending pilot and planning data, per the phase's working instructions.

---

## 1. Performance Testing Strategy

| Test Type | Purpose |
|---|---|
| Baseline performance | Establish current response-time/throughput characteristics under normal expected load |
| Load | Confirm the system meets expectations under expected peak load |
| Stress | Determine the point at which the system degrades or fails under load beyond expected peak |
| Spike | Confirm the system handles a sudden, sharp traffic increase (e.g., a medal announcement) without failure |
| Soak | Confirm the system remains stable under sustained load over an extended period (detecting memory leaks, resource exhaustion) |
| Capacity | Determine the maximum load the system can sustain before requiring scaling |
| Concurrency | Confirm correctness (not just performance) under simultaneous access, per Section 3 |
| Scalability | Confirm the system's behavior as load scales, informing future infrastructure decisions |
| Endurance | A longer-duration variant of soak testing, relevant for multi-day meet operation |
| Degradation | Confirm the system degrades gracefully (slower, not incorrect or unavailable) as load approaches capacity |
| Recovery | Confirm the system returns to normal performance after a load spike subsides |

### Prioritized Areas

Public portal · athlete search · registration import · accreditation validation · QR scanning · score entry · live scoring · Reverb fan-out · medal tally · tournament brackets · schedule boards · reports · exports · mobile sync.

These are prioritized because they are the areas most exposed to genuine load spikes during a live meet (public portal during a medal announcement, QR scanning during gate-opening/meal periods, live scoring during simultaneous events) — restated from the "Very High" and "Burst-heavy" volume categories in [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories).

**No target response-time or throughput number is set in this document** — every such value requires pilot-meet operational data or DepEd-provided planning figures (Section 2 below) before it can be meaningfully set, consistent with [../02-data/data-open-decisions.md](../02-data/data-open-decisions.md)'s and [../03-security/security-open-decisions.md](../03-security/security-open-decisions.md)'s existing "no numeric target invented" discipline.

## 2. Load-Model Inputs

Realistic load models are built from:

Number of athletes · number of delegations · number of sports · number of events · number of venues · number of officials · number of concurrent encoders · number of scanners · number of public viewers · number of real-time subscribers · upload volume · export volume · notification volume · sync volume · peak schedule (which hours see the most simultaneous activity).

**Targets must be established from pilot and planning data** — restated absolutely; a load model built from guessed inputs produces a performance test that validates nothing real. The first genuine load-model inputs are expected from [pilot-operational-and-stakeholder-validation.md, Section 1](pilot-operational-and-stakeholder-validation.md#1-pilot-validation) and DepEd's own provincial-meet scale figures (e.g., typical athlete/delegation counts for a provincial meet), not invented by engineering or QA.

## 3. Concurrency Testing

| Scenario | What to Verify |
|---|---|
| Simultaneous registration edits | Two staff editing the same registration don't silently overwrite each other |
| Simultaneous eligibility review | Two reviewers acting on the same case are correctly serialized |
| Entry locking | Entry locking at the configured tournament stage correctly blocks late changes |
| Draw generation | Concurrent draw-generation attempts for the same tournament are correctly serialized |
| Score entry | Restated from [high-integrity-sports-workflow-testing.md, Section 3](high-integrity-sports-workflow-testing.md#3-scoring-testing) |
| Score correction | Concurrent corrections to the same score are correctly serialized with a clear "winner" and audit trail |
| Result certification | Concurrent certification attempts for the same result are correctly serialized |
| Protest hold | A protest filed concurrently with a publication attempt correctly blocks that publication |
| Medal recalculation | Concurrent recalculation triggers are correctly serialized, never producing an inconsistent intermediate tally |
| Credential issuance | Concurrent issuance requests for the same participant don't produce duplicate active credentials |
| Schedule changes | Concurrent schedule edits are correctly serialized or conflict-detected |
| Assignment changes | Concurrent assignment changes for the same role/scope are correctly serialized |
| Finance approval | Concurrent approval attempts on the same transaction are correctly serialized (also enforcing SOD-06) |
| Document replacement | Concurrent uploads replacing the same document are correctly serialized with clear versioning |

## 4. Race-Condition Testing

| Scenario | What to Verify |
|---|---|
| Duplicate commands | A double-submitted command (network retry, duplicate click) doesn't duplicate its effect |
| Double-click submission | Frontend double-submission is defended against at the server, not merely the client |
| Retry after timeout | A client retry after an ambiguous timeout doesn't create a duplicate/conflicting record |
| Queue replay | A replayed queue message doesn't duplicate its effect, per idempotency design |
| Two approvers | Two authorized approvers acting near-simultaneously on the same item produce one, not two, effective outcomes |
| Stale browser form | A form submitted against outdated state is correctly rejected or safely reconciled, not blindly applied |
| Mobile and web updates | Simultaneous updates from a mobile device and the web app to the same record are correctly reconciled |
| Offline and online conflict | An offline-queued update and a server-side update to the same record are correctly detected as a conflict, per [../02-data/offline-sync-and-conflict-data-model.md, Section 3](../02-data/offline-sync-and-conflict-data-model.md#3-conflict-resolution-data) |
| Reverb event before projection | A broadcast event arriving before its corresponding read-model update completes doesn't show a client an inconsistent intermediate state |
| Result publication during protest filing | A protest filed in the narrow window before publication correctly blocks that publication rather than being silently missed |
| Credential validation during revocation | A scan occurring in the narrow window around a credential's revocation correctly resolves to the credential's actual, current status |

## 5. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably which performance-testing tool is adopted (a Phase 0.8+ decision) and when the first real load-model inputs become available (dependent on pilot scheduling).
