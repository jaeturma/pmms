# PMMS Runbooks, Playbooks, and Standard Operating Procedures

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md) · [observability-logging-metrics-tracing-and-alerting.md, Section 6](observability-logging-metrics-tracing-and-alerting.md#6-alerting-architecture)

This document defines the runbook standard and identifies the runbooks a future operational phase must produce. **No executable command or script is created here** — conceptual procedures only, per the phase's own working instruction to prefer conceptual procedures during this phase.

---

## 1. Runbook Standard

Every runbook, once written, includes:

Purpose · trigger (what condition invokes this runbook) · preconditions (what must be true before starting) · required access (which privileged-access category, per [../03-security/authorization-and-privileged-access-assurance.md, Section 3](../03-security/authorization-and-privileged-access-assurance.md#3-privileged-access-categories)) · safety warnings (specific risks of getting this wrong) · steps (the procedure itself) · verification (how to confirm the procedure worked) · rollback (what to do if it didn't) · escalation (who to involve if the runbook doesn't resolve the situation) · evidence (what to record) · owner (the role accountable for keeping it current) · last tested (runbooks are periodically exercised, not just written and forgotten) · version.

## 2. Required Future Runbooks

The following runbooks are identified as required, to be authored once the corresponding infrastructure/capability exists — **not written in this documentation-only phase**, consistent with preferring conceptual procedures now:

| Runbook | Trigger (Conceptual) |
|---|---|
| Application deployment | A scheduled or approved release |
| Rollback | A failed deployment requiring reversion, per [deployment-strategies-rollbacks-and-maintenance.md, Section 4](deployment-strategies-rollbacks-and-maintenance.md#4-rollback-architecture) |
| Queue backlog | Queue depth exceeds its expected range |
| Failed jobs | A spike in failed-job count |
| Horizon restart | Worker pool needs a supervised restart |
| Reverb restart | Real-time delivery degradation |
| Redis outage | Cache/queue-transport unavailability |
| MySQL outage | Authoritative-database unavailability — the highest-severity infrastructure runbook given MySQL's role |
| MinIO outage | Object-storage unavailability |
| Storage capacity | MinIO or MySQL approaching capacity limits |
| Certificate renewal | A certificate nearing expiry |
| Backup failure | A scheduled backup did not complete successfully |
| Restore | Per [backup-restore-disaster-recovery-and-continuity.md, Section 2](backup-restore-disaster-recovery-and-continuity.md#2-restore-architecture) |
| Device revocation | A lost/compromised device requiring immediate revocation |
| Credential rotation | A scheduled or emergency secret/credential rotation |
| Public portal failure | Public-facing availability degradation |
| Sync conflict surge | An unusual spike in offline-sync conflicts requiring investigation |
| Security incident | Per [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md) |
| Privacy incident | Per [../03-security/incident-response-and-breach-readiness.md, Section 5](../03-security/incident-response-and-breach-readiness.md#5-privacy-incident-and-breach-readiness) |
| Meet-day escalation | Per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md) |
| Emergency maintenance | An unplanned maintenance window is required |
| Data repair | Per [production-support-access-and-data-repair-operations.md, Section 4](production-support-access-and-data-repair-operations.md#4-data-repair-operations) |

## 3. Playbooks Versus Runbooks

A **runbook** is a specific, procedural response to a specific, known trigger (per Section 2). A **playbook** is a broader coordination guide for a category of situation that may not have one deterministic procedure — e.g., a "public-relations-sensitive incident" playbook coordinates roles and communication approach without prescribing exact technical steps, since those depend on the specific incident. PMMS anticipates needing both: runbooks for well-understood operational events, playbooks for the less-deterministic, judgment-requiring situations (a security incident's business/communication response, a meet-day multi-system-failure scenario).

## 4. Standard Operating Procedures

Distinct from both runbooks (reactive, trigger-based) and playbooks (coordination-focused) — an SOP documents a routine, proactively-scheduled operational task performed regularly regardless of any trigger: a weekly capacity review, a monthly access review (per [../03-security/access-review-production-and-support-controls.md](../03-security/access-review-production-and-support-controls.md)), a per-meet-cycle device inventory reconciliation (per [device-service-account-and-credential-operations.md](device-service-account-and-credential-operations.md)). SOPs are identified alongside their owning operational area throughout this `docs/05-devops/` package rather than centrally listed here, since each SOP belongs conceptually to the specific operational domain it governs.

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably the authoring schedule for the Section 2 runbook list (a Phase 0.9+ activity, sequenced against actual infrastructure implementation) and where runbooks/playbooks/SOPs are stored and version-controlled once written (a candidate addition to this `docs/05-devops/` directory itself, or a dedicated operations wiki).
