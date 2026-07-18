# ADR-0008: DevOps, Environment, CI/CD, Deployment, and Operations Architecture

## Status

Accepted (as a Phase 0.8 DevOps-architecture decision; pending formal DevOps, security, quality, operations, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 through ADR-0007 established PMMS's bounded contexts, authorization model, runtime architecture, data/persistence architecture, security/privacy/audit/governance architecture, and quality-engineering architecture. None of them specified how PMMS is actually built, shipped, deployed, monitored, or kept running — how a release moves from a developer's commit to a live meet-day system, what happens when a deployment goes wrong, how a database schema changes safely, or what "meet-day operations" actually requires beyond the software itself functioning correctly.

Left unspecified, this gap risks the same failure mode every prior phase's architecture work was built to prevent, now expressed operationally: infrastructure and deployment automation built without first agreeing on environment strategy, release-safety rules, rollback boundaries, and observability requirements — producing a platform that works until the first schema change, the first traffic spike, or the first incident reveals nobody agreed in advance what "safe" actually means. A second, PMMS-specific risk this ADR addresses directly: ordinary continuous-operation SaaS assumptions do not transfer cleanly to a platform whose highest-stakes moments (a live meet, a medal announcement) are scheduled, known in advance, and non-negotiable — an operational architecture that doesn't account for this distinction would be generically correct and specifically wrong for PMMS.

## Decision

PMMS will use a **DevOps and operations architecture that begins with an appropriately simple, evidence-grounded topology; treats every reliability capability as readiness pending implementation and evidence rather than an assumed guarantee; and preserves every architectural boundary established in ADR-0002 through ADR-0007 unchanged throughout every deployment, monitoring, and operational process it defines.**

Specifically:

1. **No capability is claimed without implementation and evidence.** High availability, disaster recovery, zero downtime, and compliance are documented as `readiness`, `candidate architecture`, or `requires validation` throughout this package — never asserted as an achieved guarantee.
2. **PMMS begins with an appropriately simple, pilot-scale topology.** A single sufficiently-sized server with process/data isolation is an acceptable, deliberate starting point — never a permanent architecture — structured (stateless application tier, independently-identifiable workloads) so that scaling toward a production-oriented, independently-scalable topology is an evolution, not a rewrite.
3. **Every DevOps recommendation is checked against actual repository evidence**, not an assumed mature-team baseline. This phase's inspection confirmed a single-commit, single-branch repository with no CI, no Docker, no `mobile/` directory, and a confirmed Pest 4/PHPUnit backend-testing foundation — every tooling recommendation in this package (CI platform, local development, versioning) is grounded in that reality.
4. **Database changes are never assumed reversible.** Schema changes follow a phased, backward-compatible pattern (add → dual-support code → backfill → validate → switch → remove old structures in a later release); where rollback isn't safe, forward-fix is the preferred and explicitly named alternative.
5. **No casual SQL edits, under any circumstance.** A production data correction follows a formal, fourteen-element data-repair process (approval, backup, dry run, controlled migration, audit, reconciliation, regression test, post-repair review) — the operational specification of Phase 0.5's emergency-repair-procedure requirement.
6. **Meet-day production changes are minimized and governed absolutely.** A live competition day triggers a change freeze, with only a narrow, still-gated emergency-change exception — never a discretionary deployment window during active competition.
7. **Redis remains disposable; MySQL and MinIO require durable, backed-up protection.** Restated unchanged from every prior phase, now given explicit operational teeth: a Redis restart or full data loss is an acceptable, non-incident event; the same is never true of MySQL or MinIO.
8. **No numeric capacity, availability, SLO, or RPO/RTO target is invented.** Every such value is a documented placeholder with a named decision owner, tracked in [../../docs/05-devops/devops-open-decisions.md](../../docs/05-devops/devops-open-decisions.md) — restated from the identical discipline already established across Phases 0.5, 0.6, and 0.7 for retention periods and RPO/RTO specifically.
9. **Deployment automation never bypasses quality, security, privacy, migration, or approval gates.** The CI/CD architecture operationalizes, and never weakens, the gates established in ADR-0006 and ADR-0007.

**Explicitly not decided by this ADR:** deployment topology and cloud-provider selection, CI platform, secret-management platform, reverse-proxy product, stateful-service hosting model (self-hosted vs. managed), container-registry selection, monitoring/observability platform, on-call tooling, DR-environment provisioning timing, numeric RPO/RTO/SLO targets, support-tier staffing model, and vulnerability-remediation SLA policy.

## Rationale

- **Preserves every prior ADR's guarantees at the layer where they are actually operated, not merely built.** An authorization model, a versioned data model, an audit architecture, and a risk-based test strategy are only as trustworthy in production as the deployment and operational discipline that keeps them intact after the first release — this ADR is where that translation happens.
- **Prevents infrastructure automation from encoding un-agreed assumptions.** Building CI/CD pipelines, deployment scripts, and monitoring configuration before environment strategy, release safety, and rollback boundaries are decided would automate exactly the wrong things faster — this ADR names the rules first, deliberately deferring the automation itself to a later phase.
- **Matches PMMS's actual operational reality, not a generic SaaS template.** The meet-day change-freeze discipline and the hybrid venue/offline-operations model exist specifically because PMMS's highest-stakes windows are scheduled and known — a generic "deploy whenever, monitor for drift" operational model would be actively wrong for this platform.
- **Grounds every recommendation in verifiable repository state rather than aspiration.** A DevOps architecture that assumed Docker, CI, and a multi-person engineering team already existed would produce guidance disconnected from what this specific repository and team actually have today — this ADR's local-development and CI recommendations are deliberately calibrated to the confirmed single-commit, native-development starting point.
- **Avoids the specific institutional risk of an overstated reliability claim.** A false "PMMS has disaster recovery" or "PMMS guarantees 99.9% uptime" claim is worse than an honest "not yet implemented, here's the readiness plan" — this ADR's evidence-based-claims discipline directly extends ADR-0006's compliance-honesty principle into infrastructure and operations.

## Approved DevOps and Operations Direction

> Begin with an appropriately simple, evidence-grounded, pilot-scale topology and native local development; establish environment, release, migration, rollback, and observability governance before any deployment automation exists; treat every capacity, availability, and recovery capability as readiness pending real evidence rather than an achieved guarantee; and preserve every architectural boundary from ADR-0002 through ADR-0007 unchanged throughout every operational process this phase defines.

## Database Release-Safety Rule (New in This Phase)

Every schema change follows the phased backward-compatible pattern; no migration is treated as trivially reversible without explicit confirmation. A migration touching a high-integrity, append-only/versioned table (per ADR-0005) receives the same phased-change scrutiny regardless of its apparent simplicity.

## Meet-Day Operations Rule (New in This Phase)

Production changes are minimized and governed on a live competition day, with a defined, still-gated emergency-change exception as the only deviation. Offline operation limits (eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, high-risk overrides never finalize offline) are restated unchanged from ADR-0003/ADR-0006/ADR-0007.

## Evidence-Based Claims Rule (New in This Phase, Extending ADR-0006)

No document, code comment, or configuration produced for PMMS states or implies high availability, disaster recovery, zero downtime, or compliance without the specific implementation and evidence to support the claim — extending ADR-0006's compliance-language discipline to every infrastructure and operational-reliability claim.

## Data-Repair Rule (New in This Phase, Extending ADR-0005)

No production data correction occurs via a direct, unreviewed SQL edit under any circumstance. The formal data-repair process is the only path, giving ADR-0005's emergency-repair-procedure requirement its full operational specification.

## Consequences

**Positive:**
- Phase 0.9 (the next implementation-adjacent phase) inherits a complete environment strategy, CI/CD gate model, deployment-safety pattern, and operational governance framework, and can begin infrastructure implementation against known, consistent expectations rather than inventing conventions per decision.
- Meet-day reliability has its operational discipline (change freeze, command center, offline fallback) named before any infrastructure exists to violate it under delivery pressure.
- The evidence-based-claims discipline protects DepEd and the platform itself from the institutional risk of an overstated reliability guarantee, while still building toward genuine operational maturity as real infrastructure and pilot evidence accumulate.

**Negative / trade-offs:**
- The phased database-release-safety pattern and the absolute "no casual SQL edits" rule add real operational overhead compared to direct, ad hoc production fixes — accepted because the alternative directly risks the high-integrity data guarantees every prior phase was built to establish.
- Deferring topology, CI platform, and stateful-service-hosting selection (all blocked on DV-01) means Phase 0.9 cannot finalize several infrastructure decisions until DepEd's deployment-model decision resolves — an accepted sequencing cost, consistent with never selecting a cloud provider without approval.
- A significant number of decisions remain open (24 items in [../../docs/05-devops/devops-open-decisions.md](../../docs/05-devops/devops-open-decisions.md)), with DV-01 (deployment topology) blocking the largest downstream dependency chain in this entire package.

## Alternatives Considered

1. **Generate actual Dockerfiles, CI workflows, and deployment scripts now to accelerate implementation.** Rejected — directly violates working rules 5–14 and this ADR's own principle that automation follows, never precedes, agreed process; would also lock in infrastructure assumptions before DV-01 (deployment topology) resolves.
2. **Commit to a specific cloud provider now to unblock every downstream infrastructure decision.** Rejected — no provider is currently approved, per working rule 23; DV-01 remains deliberately open pending DepEd's actual infrastructure/budget decision, exactly as the deployment-model question has remained open since Phase 0.1.
3. **Claim high availability and disaster-recovery capability based on this documentation alone, to reassure DepEd stakeholders early.** Rejected — directly violates working rule 24 and this ADR's evidence-based-claims rule; every such capability remains explicitly gated on future implementation and demonstrated evidence.
4. **Treat meet-day operations as an ordinary continuous-operation SaaS scenario, applying generic DevOps practices without modification.** Rejected — PMMS's meet-day reliability requirements are qualitatively different from ordinary always-on SaaS operation, justifying the dedicated change-freeze and command-center model in [../../docs/05-devops/meet-day-venue-and-offline-operations.md](../../docs/05-devops/meet-day-venue-and-offline-operations.md).
5. **Allow direct production database edits for urgent fixes, given the operational overhead of the formal data-repair process.** Rejected — per ADR-0005's original prohibition, restated absolutely here; the operational cost of the formal process is deliberately accepted in exchange for protecting the platform's highest-stakes data from an unreviewed, unaudited edit.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated Infrastructure owner, DevOps reviewer, Security owner, Quality owner, technical lead, DepEd Leadership, and ICT operations staff, per [../../docs/05-devops/README.md, "Ownership and Review Expectations"](../../docs/05-devops/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.8 open decisions, per [../../docs/05-devops/devops-open-decisions.md, "Summary of Blocking / High-Priority DevOps Decisions"](../../docs/05-devops/devops-open-decisions.md#summary-of-blocking--high-priority-devops-decisions) — notably DV-01 (deployment topology, blocking) and DV-17 (RPO/RTO targets, unresolved across five consecutive phases).
- Continued resolution of the Phase 0.1 policy and deployment-model decisions this ADR's topology and operational rules depend on (OD-02 multi-organization support, OD-17 deployment model).

## Related Documents

- [../../docs/05-devops/phase-0.8-devops-environment-cicd-deployment-operations.md](../../docs/05-devops/phase-0.8-devops-environment-cicd-deployment-operations.md)
- [../../docs/05-devops/devops-and-platform-operations-strategy.md](../../docs/05-devops/devops-and-platform-operations-strategy.md)
- [../../docs/05-devops/deployment-topology-and-runtime-units.md](../../docs/05-devops/deployment-topology-and-runtime-units.md)
- [../../docs/05-devops/database-migration-and-release-safety.md](../../docs/05-devops/database-migration-and-release-safety.md)
- [../../docs/05-devops/production-support-access-and-data-repair-operations.md](../../docs/05-devops/production-support-access-and-data-repair-operations.md)
- [../../docs/05-devops/meet-day-venue-and-offline-operations.md](../../docs/05-devops/meet-day-venue-and-offline-operations.md)
- [../../docs/05-devops/backup-restore-disaster-recovery-and-continuity.md](../../docs/05-devops/backup-restore-disaster-recovery-and-continuity.md)
- [../../docs/05-devops/devops-open-decisions.md](../../docs/05-devops/devops-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../devops-rules.md](../devops-rules.md)
- [../deployment-rules.md](../deployment-rules.md)
- [../environment-rules.md](../environment-rules.md)
- [../operations-rules.md](../operations-rules.md)
- [../observability-rules.md](../observability-rules.md)
- [../incident-rules.md](../incident-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
- [ADR-0005-data-database-and-information-lifecycle-architecture.md](ADR-0005-data-database-and-information-lifecycle-architecture.md)
- [ADR-0006-security-privacy-audit-compliance-and-data-governance.md](ADR-0006-security-privacy-audit-compliance-and-data-governance.md)
- [ADR-0007-quality-engineering-testing-validation-and-assurance.md](ADR-0007-quality-engineering-testing-validation-and-assurance.md)
