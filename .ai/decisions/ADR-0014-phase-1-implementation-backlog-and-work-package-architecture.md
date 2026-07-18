# ADR-0014: Phase 1 Implementation Backlog, Work Packages, Acceptance Criteria, and Definition of Done

## Status

Accepted (as a Phase 0.14 backlog-architecture decision; pending backlog, architecture, product, quality, and engineering review — see [../current-phase.md](../current-phase.md) and [../../docs/11-backlog/README.md](../../docs/11-backlog/README.md))

## Context

ADR-0013 closed the Phase 0 architecture effort with the assessment "Phase 0 Requires Targeted Remediation": zero material contradictions across twelve phases, 19 of 30 major capabilities Ready for Backlog Decomposition or Ready with Constraints, six modules blocked on the OD-07/08/09/10/12/15 policy cluster, five capability categories correctly deferred, and one critical completeness gap — no physical database schema had ever been produced (GAP-01). ADR-0013 explicitly excluded backlog generation from its own scope and named it Phase 0.14's distinct responsibility.

Without a disciplined conversion step, a twelve-phase architecture corpus this large fails in one of two ways when implementation begins: either implementation sessions re-derive scope ad hoc from hundreds of documents (inconsistently, expensively, and with silent scope drift), or the corpus is bypassed entirely and the starter kit grows undocumented code with no traceability to the architecture that was supposed to govern it. The repository at this point remains the unmodified Laravel 13 React starter kit — zero PMMS domain code — so every claim in the backlog about "what exists" is checkable evidence, not recollection.

## Decision

Phase 0.14 produced a complete, reviewable, non-implemented Phase 1 backlog under [docs/11-backlog/](../../docs/11-backlog/): 16 top-level documents (scope/release strategy, epic catalog, work-package catalog, execution sequence, dependency map, traceability matrix, definition of ready, definition of done, quality gates, review/sign-off model, risk register, decision register, deferred scope, completion-evidence standard, plus the main document and README) and **155 work-package documents across 15 epics**, each using a mandatory 31-section template ending in a ready-to-paste future execution prompt (`Start WP-XX-YY`).

Specifically:

1. **Phase 1 is Core Foundation Implementation only.** Fifteen epics: engineering baseline (EPIC-01), modular monolith (EPIC-02), identity/authentication (EPIC-03), organization/meet context (EPIC-04), authorization (EPIC-05), audit (EPIC-06), reference data (EPIC-07), file/object storage (EPIC-08), queue/scheduler/notification/real-time (EPIC-09), API/Inertia conventions (EPIC-10), PMMS Arena web design system (EPIC-11), Flutter foundation (EPIC-12), observability (EPIC-13), data protection/secure development (EPIC-14), and integration validation/sign-off (EPIC-15). No sports-management module, no AI, no billing, no multi-tenancy activation, no SSO, no sharding, no Kubernetes. See [../../docs/11-backlog/phase-0.14-phase-1-implementation-backlog.md, Sections 4–5](../../docs/11-backlog/phase-0.14-phase-1-implementation-backlog.md).
2. **The policy-blocked modules are excluded, not deferred-with-hope.** Eligibility, Official Results, Protest and Appeals, Medal Tally, Medical Operations, Finance, and sport-specific scoring/brackets are out of Phase 1 entirely, so the unresolved OD-07/08/09/10/12/15 cluster and DD-01 do not block Phase 1 start — deliberately breaking the compounding-bottleneck risk ARR-09 warned about.
3. **GAP-01 (physical schema) is resolved as a decomposed design discipline, not one monolithic schema work package.** Every persistence-touching work package carries a mandatory Section 9 proposing its own tables/keys/constraints, individually reviewable; WP-15-02 performs the consolidated schema/migration review at the end.
4. **Work packages are Small/Medium/Large only — never Extra Large, never time-estimated.** 155 total; roughly 30% Small, 55% Medium, 15% Large; priorities P1 (core foundation) and P2 (integration/validation), with P0 reserved for governance items tracked outside the backlog.
5. **Six sequential release groups (A–F)** — Engineering/Architecture → Identity/Context/Authorization → Audit/Data/Files/Configuration → Runtime/API/Web Experience → Mobile/Operations/Security → Integration and Sign-Off — with documented parallelism inside groups, a single entry point (WP-01-01) and a single exit gate (WP-15-12, the evidence-gated Foundation Sign-Off Package).
6. **Every work package binds to shared standards** — one Definition of Ready, one Definition of Done, one completion-evidence standard, and one quality-gate set — rather than restating them 155 times; acceptance criteria prohibit unmeasurable language.
7. **Existing starter-kit capability is normalized or verified, never re-invented** — login, 2FA, passkeys, and password reset exist and work; work packages covering them are classified Normalization or Verification-only, and every work package's Section 7 requires fresh repository inspection before execution.
8. **Nothing was implemented.** No code, migration, model, component, test, package installation, CI workflow, or infrastructure change was made in Phase 0.14; every proposed name is labeled proposed; every work package begins at `Planned — Not Started`.

**Explicitly not decided by this ADR:** execution of any work package; resolution of any open policy, retention, RPO/RTO, deployment-topology, WCAG-target, or provider decision; the actual physical schema (only the discipline for designing it); assignment of the product/technical owners and reviewers every work package lists as "To be identified" (GAP-13 carried forward, hard-blocking WP-15-11/12).

## Rationale

- **One work package at a time is the only session-executable shape for this corpus.** The 31-section template with a closing execution prompt lets a future session be told `Start WP-01-01` and proceed with full architectural context, explicit exclusions, and evidence requirements — without re-pasting twelve phases of architecture.
- **Excluding the policy-blocked modules keeps Phase 1 startable today.** Every one of the 155 work packages is executable without resolving the policy cluster; the alternative (including blocked modules "optimistically") would have reproduced the exact sequencing failure Phase 0.13 flagged.
- **Decomposed schema design keeps reviews honest.** A single 34-context schema document would be unreviewably large; per-work-package Section 9 proposals are individually reviewable and land next to the code that uses them, with WP-15-02 as the consolidated backstop.
- **The evidence discipline of ADR-0013 is inherited wholesale.** Status models, readiness classifications, completion-evidence requirements, and the sign-off package all carry the Documented→Formally-Accepted evidence ladder forward, so "backlog exists" can never quietly become "foundation complete."

## Approved Backlog Direction

> Execute Phase 1 as 155 work packages across 15 epics in release groups A→F, starting at WP-01-01 and converging at WP-15-12, limited strictly to foundation scope, with per-work-package schema design, shared ready/done/evidence standards, and no work package marked complete without its required evidence — while every sports-module, AI, and enterprise capability remains excluded pending its own decisions and approvals.

## Consequences

**Positive:**
- Phase 1 can begin immediately at WP-01-01 with no unresolved Phase 0 decision on its critical path.
- Every future implementation session has a bounded, reviewable, evidence-gated unit of work with explicit exclusions — scope drift requires visibly violating a document, not just forgetting one.
- The traceability matrix ties every work package back to Phase 0 findings, preserving the audit trail from architecture to implementation.

**Negative / trade-offs:**
- 155 documents are themselves a maintenance surface; a mid-Phase-1 architectural correction may require touching many work packages (accepted: the catalog and dependency map centralize the cross-references to make that tractable).
- Foundation-only scope means Phase 1 delivers no user-visible sports capability — stakeholder expectations must be managed accordingly.
- GAP-13 (unassigned owners/reviewers) is carried, not solved; it hard-blocks the final acceptance work packages (WP-15-11, WP-15-12) if left unresolved through Phase 1.

## Alternatives Considered

1. **A flat issue list (titles + descriptions) instead of full work-package documents.** Rejected — session-executability requires each unit to carry its own scope, exclusions, dependencies, security/privacy requirements, and evidence standard; a flat list pushes all of that back into ad-hoc re-derivation.
2. **One up-front physical-schema work package before all others.** Rejected — unreviewably large, and it would serialize all epics behind a single document; the decomposed Section 9 discipline plus WP-15-02's consolidated review achieves the same coverage reviewably.
3. **Including the policy-blocked modules as "blocked" backlog items.** Rejected — it would misrepresent Phase 1's deliverable, invite premature starts, and re-couple Phase 1 to the unresolved policy cluster.
4. **Time-based estimates (hours/days) on work packages.** Rejected — no calibration data exists for this team/codebase; complexity classes carry the needed sequencing information without inventing numbers, consistent with the corpus-wide no-invented-numbers discipline.
5. **Beginning implementation directly (Phase 1) without a formal backlog phase.** Rejected — this is the exact conversion step ADR-0013 required; skipping it reintroduces the sequential-authorship risk at the implementation layer.

## Validation Requirements

This decision is provisional pending:

- Backlog review by the candidate reviewer roles (architecture, product, quality, security, engineering) — none yet assigned (GAP-13).
- Resolution of GAP-13 before Release F's acceptance work packages (WP-15-11, WP-15-12) can start.
- Continued pursuit of the OD-07/08/09/10/12/15 policy cluster in parallel with Phase 1, so the phase after Phase 1 is not blocked the way Phase 1 refused to be.

## Related Documents

- [../../docs/11-backlog/README.md](../../docs/11-backlog/README.md) — Phase 0.14 documentation index and reading order
- [../../docs/11-backlog/phase-0.14-phase-1-implementation-backlog.md](../../docs/11-backlog/phase-0.14-phase-1-implementation-backlog.md) — primary Phase 0.14 document
- [../../docs/11-backlog/phase-1-work-package-catalog.md](../../docs/11-backlog/phase-1-work-package-catalog.md) — all 155 work packages
- [../../docs/11-backlog/phase-1-epic-catalog.md](../../docs/11-backlog/phase-1-epic-catalog.md) — all 15 epics
- [../../docs/11-backlog/phase-1-execution-sequence.md](../../docs/11-backlog/phase-1-execution-sequence.md) — sequencing and release gates
- [ADR-0013-final-architecture-validation-and-readiness-review.md](ADR-0013-final-architecture-validation-and-readiness-review.md) — the readiness assessment this backlog converts
- [../implementation-backlog-rules.md](../implementation-backlog-rules.md) — condensed AI-facing rules for working with this backlog
