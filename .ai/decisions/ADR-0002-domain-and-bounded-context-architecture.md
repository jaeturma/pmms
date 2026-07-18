# ADR-0002: Domain-Oriented Modular Monolith with Explicit Bounded Contexts

## Status

Accepted (as a Phase 0.2 domain-architecture decision; pending formal domain-expert and stakeholder sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0001 established that PMMS is a configurable, multi-meet, enterprise-grade platform, not a one-time event website or a basic registration/tally tool. That decision left open *how* such a broad platform — spanning at least 34 distinct areas of business responsibility, from athlete identity through scoring, medal tally, logistics, and public communication — should be decomposed for actual implementation.

Left undecomposed, PMMS risks becoming a single undifferentiated data model where a change to a low-stakes area (e.g., transportation manifests) can accidentally destabilize a high-stakes one (e.g., official results), simply because both live in the same tables and the same application without an examined boundary. Phase 0.1 explicitly named five domains — scoring/official results, athlete eligibility, medical data, accreditation, and medal tally — as high-integrity, requiring human validation, no silent changes, and source-backed rules. Phase 0.2 exists to translate that requirement into an architectural boundary, before database schema or application code exists to make the boundary expensive to fix.

## Decision

PMMS will use a **domain-oriented modular monolith** for its initial implementation, structured as **34 explicit bounded contexts** (13 Core, 16 Supporting, 5 Generic — see [../../docs/01-architecture/bounded-context-catalog.md](../../docs/01-architecture/bounded-context-catalog.md)), with:

1. **One authoritative owner for each major data concept.** No context holds a second authoritative copy of another context's data; downstream consumers hold references, projections, caches, or snapshots only (see [../../docs/01-architecture/data-ownership-map.md](../../docs/01-architecture/data-ownership-map.md)).
2. **Internal event-driven integration.** Contexts communicate primarily through conceptual domain events (cataloged in [../../docs/01-architecture/domain-events-catalog.md](../../docs/01-architecture/domain-events-catalog.md)) rather than direct cross-context queries or writes, using named DDD relationship patterns (Customer–Supplier, Anti-Corruption Layer, Published Language, Open Host Service, Partnership) documented per relationship in [../../docs/01-architecture/context-map.md](../../docs/01-architecture/context-map.md).
3. **Controlled public projections.** Public Information (BC-29) and Reporting and Analytics (BC-33) hold no authoritative data of their own and issue no writes upstream — they are strictly downstream, isolating public/reporting traffic from transactional integrity (see [../../docs/01-architecture/reporting-and-read-model-boundaries.md](../../docs/01-architecture/reporting-and-read-model-boundaries.md)).
4. **High-integrity domain safeguards.** Eleven domains (participant identity, athlete registration, eligibility, competition entries, tournament progression, scoring, official results, protests/appeals, medal tally, accreditation, medical information — plus financial records and audit history) carry dedicated architectural safeguards: no silent mutation, explicit state transitions, separation of duties, versioned correction instead of destructive overwrite, and AI limited to advisory-only assistance (see [../../docs/01-architecture/high-integrity-domain-rules.md](../../docs/01-architecture/high-integrity-domain-rules.md)).
5. **Extraction-ready boundaries.** The monolith is structured so that any bounded context — most plausibly Scoring (BC-15), Access Validation (BC-20), or Public Information (BC-29), given their distinct offline/high-volume/public-traffic profiles — could be extracted into an independent service later, because its data ownership and interfaces are already explicit. This is not a commitment to extract; it is a design property that keeps the option open at low cost.

**Explicitly not decided by this ADR:** deployment topology (cloud/on-prem/hybrid), role/permission/scope architecture (Phase 0.3), database schema, API/route design, UI design, and the specific synchronization protocol for offline-capable contexts.

## Rationale

- **Avoids the CRUD-collapse failure mode.** An undifferentiated single application makes it structurally easy for a low-stakes change to compromise a high-integrity one, because nothing in the system's structure reflects the difference in stakes. Explicit bounded contexts make that difference visible and enforceable.
- **Avoids premature microservice complexity.** Nothing in the Phase 0.1/0.2 evidence (team size, confirmed traffic patterns, confirmed connectivity conditions) justifies the operational cost of distributed services from day one. Key domain questions (eligibility rules, sports rules, medal rules — still open per [Phase 0.1](../../docs/00-product/open-decisions.md)) are cheaper to resolve inside a single deployable than across already-separated services.
- **Matches the confirmed technology stack.** Laravel's module-friendly architecture (service providers, event/listener dispatch, Horizon-based queues, Reverb for real-time broadcast) supports bounded-context discipline within one application without requiring service-per-context deployment.
- **Protects the five named high-integrity domains directly.** Each has an explicit authoritative owner, explicit correction pattern, and explicit AI-advisory-only boundary, directly operationalizing the Phase 0.1 product principles rather than leaving them as unenforced intent.
- **Keeps future options open.** Because ownership and interfaces are explicit per context, a future decision to extract a specific high-traffic or offline-critical context does not require re-deriving domain boundaries from a tangled codebase.

## Approved Architecture Direction

> Domain-oriented modular monolith for initial implementation, with explicit bounded contexts, event-driven internal integration, and extraction-ready boundaries.

## Domain Classification

13 Core bounded contexts (Participant Registry, Athlete Registration, Eligibility and Clearance, Sports Catalog, Competition Entries, Tournament Management, Scoring, Official Results, Protest and Appeals, Medal Tally and Team Standings, Accreditation, Access Validation, Meet Administration), 16 Supporting, 5 Generic. Full rationale: [../../docs/01-architecture/domain-classification.md](../../docs/01-architecture/domain-classification.md).

## Bounded-Context Principles

- Contexts are defined by business responsibility, language, ownership, consistency requirements, and lifecycle — never by page names, database table names, or committee org-chart names alone.
- No single oversized "Meet Management" context; Meet Administration is scoped strictly to meet identity/lifecycle, with committee, delegation, venue, and competition concerns each owning their own context.
- No context created for every minor feature; specialized committee contexts (Medical, Security, Finance) exist only where their data/lifecycle/integrity needs are genuinely distinct, not merely because an org chart names them.

## Data Ownership Rule

Every major data concept has exactly one authoritative bounded context. Consumers hold references, projections, caches, or snapshots — never a second authoritative copy. Corrections happen only at the authoritative owner, through that context's defined correction pattern.

## Integration Rule

Every cross-context data flow uses a named DDD relationship pattern. **Shared Kernel is avoided everywhere** except one narrow, explicitly justified, version-pinned Published Language treatment of shared reference data (Configuration and Reference Data, BC-34) — never a live mutable shared table.

## High-Integrity Domain Rule

The eleven high-integrity domains require: no silent mutation, explicit state transitions, actor and reason capture, timestamping, versioning, separation of duties, evidence retention, correction-not-overwrite, controlled publication, human approval, restricted access, testable invariants, immutable history where appropriate, and AI advisory-only behavior.

## Public Data Rule

Public Information owns no authoritative data, consumes only approved projections from upstream contexts, and issues no writes upstream. Public traffic is architecturally isolated from transactional write-path performance.

## Offline Authority Rule

Offline-capable devices may capture and locally sequence data but may never finalize: eligibility approval, official result certification, protest resolution, medal tally certification, meet closure, or destructive administrative changes. Credential revocation propagation to offline scanners is treated as highest sync priority.

## AI Authority Rule

AI may assist (detection, summarization, recommendation) in every context but never independently decides a high-integrity outcome — no autonomous eligibility approval, result certification, score alteration, disqualification, protest resolution, medal awarding, or medical decision, and no silent modification of official data anywhere in the platform.

## Consequences

**Positive:**
- Phase 0.3 (roles/permissions) and later architecture phases inherit a validated set of boundaries and an explicit list of actor types per workflow ([../../docs/01-architecture/workflow-and-command-catalog.md](../../docs/01-architecture/workflow-and-command-catalog.md)), rather than needing to reverse-engineer them from code.
- High-integrity domains have architectural safeguards defined before any implementation exists to violate them.
- The monolith-first choice keeps near-term delivery cost proportionate to team size while preserving a credible extraction path.

**Negative / trade-offs:**
- A modular monolith requires internal discipline (module boundaries, event dispatch conventions) that is easier to violate than a hard service boundary would be — this discipline must be actively maintained in the implementation phase, not assumed to hold automatically from documentation alone.
- Several Core contexts' detailed workflows remain blocked on Phase 0.1 policy decisions (eligibility authority, result approval chain, protest authority, medal tally rules), meaning Phase 0.3 cannot fully assign roles to those workflows until DepEd resolves them.
- 34 bounded contexts is a substantial number to hold in mind; this is mitigated by the classification into Core/Supporting/Generic and by the consistent glossary, but remains a real cognitive-load cost for new contributors.

## Alternatives Considered

1. **Single modular monolith without explicit bounded contexts.** Rejected — provides none of the ownership/boundary protection this ADR is meant to establish; effectively the CRUD-collapse failure mode this decision exists to avoid.
2. **Microservices from the beginning.** Rejected for the initial build — introduces distributed-systems complexity (network partitions, eventual-consistency debugging, deployment orchestration) disproportionate to current team size and to a domain model still being validated through Phase 0.1/0.2 stakeholder input. May be revisited for specific high-traffic/offline-critical contexts once real operating conditions are known.
3. **Hybrid modular monolith with independently scalable public/real-time components.** Partially adopted in spirit — the non-authoritative, isolated design of Public Information (BC-29) achieves the most valuable part of this alternative without committing to a second deployable at this stage.
4. **Separate operational and public applications.** Considered and folded into the recommended direction — achieved through BC-29's read-only, non-authoritative design rather than a literal second application at this stage.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated software architect and domain-expert reviewers (sports specialists, DepEd policy stakeholders) — see consultation priorities in [../../docs/00-product/stakeholder-register.md](../../docs/00-product/stakeholder-register.md).
- Resolution of the domain-modeling decisions flagged as blocking Phase 0.3 in [../../docs/01-architecture/domain-open-decisions.md, "Summary of Decisions Blocking Phase 0.3"](../../docs/01-architecture/domain-open-decisions.md#summary-of-decisions-blocking-phase-03).
- Continued resolution of the Phase 0.1 product-level open decisions this ADR's high-integrity contexts depend on (notably [OD-07](../../docs/00-product/open-decisions.md#od-07--eligibility-authority), [OD-08](../../docs/00-product/open-decisions.md#od-08--official-result-approval-chain), [OD-09](../../docs/00-product/open-decisions.md#od-09--protest-and-appeal-authority), [OD-10](../../docs/00-product/open-decisions.md#od-10--sports-rule-source), [OD-12](../../docs/00-product/open-decisions.md#od-12--medal-tally-rules)).

## Related Documents

- [../../docs/01-architecture/phase-0.2-domain-architecture.md](../../docs/01-architecture/phase-0.2-domain-architecture.md)
- [../../docs/01-architecture/bounded-context-catalog.md](../../docs/01-architecture/bounded-context-catalog.md)
- [../../docs/01-architecture/domain-classification.md](../../docs/01-architecture/domain-classification.md)
- [../../docs/01-architecture/context-map.md](../../docs/01-architecture/context-map.md)
- [../../docs/01-architecture/data-ownership-map.md](../../docs/01-architecture/data-ownership-map.md)
- [../../docs/01-architecture/high-integrity-domain-rules.md](../../docs/01-architecture/high-integrity-domain-rules.md)
- [../../docs/01-architecture/domain-open-decisions.md](../../docs/01-architecture/domain-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
