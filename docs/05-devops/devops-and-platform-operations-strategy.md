# PMMS DevOps and Platform Operations Strategy

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/phase-0.4-application-integration-runtime-architecture.md](../01-architecture/phase-0.4-application-integration-runtime-architecture.md) · [../04-quality/quality-engineering-strategy.md](../04-quality/quality-engineering-strategy.md) · [environment-architecture.md](environment-architecture.md)

This document defines PMMS's DevOps vision, principles, and platform-operations responsibility model. **No infrastructure, CI, or deployment configuration is created here.**

---

## 1. DevOps Vision

PMMS's DevOps architecture exists to make **repeatability, safety, automation, traceability, security, reliability, observability, recoverability, reproducibility, controlled change, environment consistency, operational simplicity, scalability, cost awareness, documentation, and sustainable support** the default outcome of every deployment, not an occasional achievement — because a platform DepEd runs live meets on cannot depend on a specific engineer's memory of "how we usually do this."

## 2. DevOps Principles

1. **Infrastructure changes are reviewed** — no infrastructure-affecting change ships without review, the same discipline already required for application code.
2. **Builds are reproducible** — the same source and lock-file state produces the same build output every time.
3. **Artifacts are immutable after creation** — a built artifact is never modified in place; a change produces a new artifact.
4. **Deploy the same artifact across environments where practical** — reducing "it worked in staging" surprises by promoting one built thing rather than rebuilding per environment.
5. **Secrets remain external to source control** — restated absolutely from every prior phase's secret-management discipline.
6. **Configuration is environment-specific but governed** — per-environment values differ; the schema and validation of what's configurable does not.
7. **Automate repeatable work** — manual, repetitive operational tasks are automation candidates once their manual form is well-understood.
8. **Keep manual emergency procedures documented** — automation failure must not leave operators without a known manual path.
9. **Deployments must be observable** — a deployment's success or failure is confirmable, not assumed.
10. **Rollback or forward-fix must be planned** before a change ships, not improvised during an incident.
11. **Database changes require special safeguards** — restated absolutely; schema changes are not treated like ordinary application code changes.
12. **Public and administrative workloads must be protected from each other** — a public traffic spike must never degrade administrative/scoring capacity, restated from Phase 0.4.
13. **Redis is disposable and transient** — restated absolutely from every prior phase; no operational practice may treat it otherwise.
14. **MySQL and MinIO require durable protection** — the two genuinely authoritative stores receive commensurate operational care.
15. **Reverb is transient and recoverable** — a Reverb restart is an acceptable operational event, never a data-loss event.
16. **Queue jobs must be retry-safe** — restated from Phase 0.4/0.7; idempotency is an operational assumption, not an aspiration.
17. **Production access is exceptional** — restated from Phase 0.6; not a routine engineering convenience.
18. **Meet-day changes are minimized** — restated per working rule 45; a live meet is not the time for a discretionary deployment.
19. **Fail safely** — an operational failure denies/queues/degrades rather than corrupting or silently succeeding incorrectly.
20. **Restore capability must be tested** — a backup nobody has restored from is not a verified backup.
21. **Documentation is part of operations** — a runbook, not just working code, is a deliverable of operational readiness.

## 3. Platform Operations Model

| Operational Area | Responsibility |
|---|---|
| Application operations | Deployment, configuration, release health of the Laravel/Inertia/React application |
| Database operations | MySQL health, backup, migration safety, capacity |
| Redis operations | Cache/queue/lock/session health, never authoritative-data protection |
| MinIO operations | Object-storage health, backup, reconciliation |
| Reverb operations | Real-time broadcast health, connection capacity |
| Queue and Horizon operations | Worker health, backlog, failed-job management |
| Scheduler operations | Scheduled-task execution and failure detection |
| Mobile release operations | Flutter app build/release/compatibility management |
| Device operations | Scanner/encoder/kiosk device fleet management |
| Public portal operations | Public-facing availability and traffic isolation |
| Security operations | Restated from [../03-security/](../03-security/) — incident response, vulnerability management |
| Backup operations | Backup execution, verification, restore testing |
| Incident response | Cross-cutting, per [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md) |
| Support | Tiered support model, per [production-support-access-and-data-repair-operations.md](production-support-access-and-data-repair-operations.md) |
| Vendor management | Restated from [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md) — no vendor currently approved |
| Meet-day command center | Per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md) |

No named individual is assigned to any operational area in this documentation — every area names a role/function, to be identified per [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md), consistent with every prior phase's governance treatment.

## 4. Relationship to Prior Phases

This document does not redefine anything already decided: **MySQL remains authoritative; Redis remains transient; MinIO stores object content with MySQL-controlled metadata; Reverb delivers transient real-time messages; Horizon supervises queue workers; public projections remain downstream; mobile and venue clients remain subject to server authority** — restated absolutely from working rules 32–38, carried unchanged from Phase 0.4/0.5/0.6/0.7.

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) for every unresolved DevOps governance and operational-model question this document depends on.
