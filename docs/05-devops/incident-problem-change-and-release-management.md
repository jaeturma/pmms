# PMMS Incident, Problem, Change, and Release Management

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md) · [../04-quality/release-readiness-and-quality-signoff.md](../04-quality/release-readiness-and-quality-signoff.md) · [runbooks-playbooks-and-standard-operating-procedures.md](runbooks-playbooks-and-standard-operating-procedures.md)

This document defines incident management, on-call readiness, problem management, change management, and release management for PMMS operations. **No incident-management tooling, on-call scheduling software, or CI/CD automation is created here.**

---

## 1. Incident Management

Extends [../03-security/incident-response-and-breach-readiness.md, Section 1](../03-security/incident-response-and-breach-readiness.md#1-security-incident-response-lifecycle) (which covers security/privacy incidents specifically) to **operational incidents generally** — an outage, a performance degradation, a failed deployment — using the same disciplined lifecycle:

```text
Detect → Acknowledge → Classify → Triage → Contain → Communicate → Resolve → Recover → Verify → Review → Improve
```

| Element | Direction |
|---|---|
| Severity placeholders | Mirrors [../04-quality/defect-triage-root-cause-and-quality-debt.md, Section 2](../04-quality/defect-triage-root-cause-and-quality-debt.md#2-severity-model) — Critical/High/Moderate/Low, no numeric SLA fixed |
| Incident commander | Leads the incident through the lifecycle, per [../03-security/incident-response-and-breach-readiness.md, Section 3](../03-security/incident-response-and-breach-readiness.md#3-roles-and-escalation) |
| Technical lead | Diagnoses and directs technical remediation |
| Communications lead | Coordinates internal/external communication |
| Security or privacy escalation | Per [../03-security/incident-response-and-breach-readiness.md, Section 5](../03-security/incident-response-and-breach-readiness.md#5-privacy-incident-and-breach-readiness) if the operational incident has security/privacy implications |
| Business owner | The affected domain's owner, confirming business impact and validating recovery |
| Timeline | Maintained throughout, from detection to closure |
| Evidence | Preserved before remediation risks losing it, restated from [../03-security/incident-response-and-breach-readiness.md, Section 1](../03-security/incident-response-and-breach-readiness.md#1-security-incident-response-lifecycle) |
| Status updates | Communicated at a defined cadence during an active incident |
| Resolution | The immediate fix that ends the incident |
| Post-incident review | Per Section 3 below |

## 2. On-Call Readiness

A future operational capability once the platform has live production traffic to protect: a defined on-call rotation (roles, not named individuals), an escalation path (Section, [observability-logging-metrics-tracing-and-alerting.md, Section 6](observability-logging-metrics-tracing-and-alerting.md#6-alerting-architecture)), and defined response expectations — **not yet built**, since no production environment currently exists to be on-call for. This document establishes the readiness requirement; the actual rotation/tooling is a Phase 0.9+ operational decision.

## 3. Problem Management

Distinct from incident management — an incident restores service; problem management prevents recurrence:

Recurring-incident analysis · trend detection (are the same category of incident happening repeatedly?) · root cause (per [../04-quality/defect-triage-root-cause-and-quality-debt.md, Section 4](../04-quality/defect-triage-root-cause-and-quality-debt.md#4-root-cause-analysis), extended to operational incidents) · known errors (a documented, recognized recurring issue with an interim workaround) · workaround (the interim mitigation while a permanent fix is pending) · permanent fix · owner · risk · release planning (the permanent fix enters the ordinary release process, per Section 5) · verification · closure.

## 4. Post-Incident Review

Every declared incident (operational, security, or privacy) receives a post-incident review producing: a timeline, root cause, what worked, what didn't, and specific control improvements — restated from [../03-security/incident-response-and-breach-readiness.md, Section 7](../03-security/incident-response-and-breach-readiness.md#7-post-incident-review), extended here to operational incidents specifically, tracked to completion in [devops-open-decisions.md](devops-open-decisions.md) or the relevant runbook update (per [runbooks-playbooks-and-standard-operating-procedures.md](runbooks-playbooks-and-standard-operating-procedures.md)).

## 5. Change Management

### Change Classification

| Type | Characteristics |
|---|---|
| Standard change | Pre-approved, low-risk, routine (e.g., a documented dependency patch following the established process) |
| Normal change | Requires review and approval through the ordinary pull-request/release process |
| Emergency change | Expedited due to an active incident, still requires minimum gates and mandatory post-review, per [ci-cd-and-release-pipeline-architecture.md, Section 4](ci-cd-and-release-pipeline-architecture.md#4-continuous-deployment-boundaries) |
| Security change | Requires Security reviewer involvement, per [../03-security/secure-development-lifecycle.md](../03-security/secure-development-lifecycle.md) |
| Database change | Requires the full migration-gate discipline, per [database-migration-and-release-safety.md](database-migration-and-release-safety.md) |
| Configuration change | Per Section 6 below |
| Meet-day restricted change | Subject to the change freeze in [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md), restated absolutely from working rule 45 |

### Every Change Requires

Scope · risk · testing · approval · schedule · backup (where the change touches data) · rollback or forward-fix plan (per [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md)) · communication · verification · evidence.

## 6. Configuration-Change Governance

A configuration change (not just a code change) follows the same review discipline as any other change — restated from [configuration-feature-flag-and-secret-management.md, Section 2](configuration-feature-flag-and-secret-management.md#2-configuration-rules), Rule 5. A sensitive configuration change (session lifetime, rate limits, CORS origins, feature-flag state affecting a Critical-tier domain) is itself audit-relevant and requires the same elevated review as a security-relevant code change.

## 7. Release Management

| Element | Direction |
|---|---|
| Release candidate | A specific, tagged build proposed for release |
| Release notes | What changed, in business-readable terms |
| Included changes | The specific commits/work-items in this release |
| Known issues | Anything knowingly incomplete or deferred, per [../04-quality/release-readiness-and-quality-signoff.md, Section 1](../04-quality/release-readiness-and-quality-signoff.md#1-release-quality-gates) ("known limitations documented") |
| Dependencies | Any dependency updates included |
| Migration plan | Per [database-migration-and-release-safety.md](database-migration-and-release-safety.md) |
| Deployment plan | Per [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md) |
| Verification | Post-deployment confirmation the release behaves as intended |
| Rollback plan | Per [deployment-strategies-rollbacks-and-maintenance.md, Section 4](deployment-strategies-rollbacks-and-maintenance.md#4-rollback-architecture) |
| Monitoring | What's specifically watched post-release, per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md) |
| Support readiness | Support tiers (per [production-support-access-and-data-repair-operations.md](production-support-access-and-data-repair-operations.md)) are briefed on what's changing |
| Approval | Per [../04-quality/release-readiness-and-quality-signoff.md, Section 2](../04-quality/release-readiness-and-quality-signoff.md#2-release-sign-off) — this document does not redefine Phase 0.7's sign-off model, it operationalizes the deployment mechanics around it |
| Closure | The release is formally closed once verification and initial monitoring confirm success |

## 8. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably on-call tooling/rotation design (deferred until production exists) and the specific severity-to-response-time mapping (mirrors [../04-quality/quality-open-decisions.md, QD-06](../04-quality/quality-open-decisions.md#qd-06--severity-to-response-time-mapping)).
