# PMMS Security Metrics, Monitoring, and Reporting

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md) · [compliance-control-framework.md](compliance-control-framework.md) · [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md)

This document defines candidate security metrics, release-security gates, and compliance-reporting structure. **No monitoring dashboard, alerting rule, or CI gate is created here.**

---

## 1. Security Metrics (Candidates)

| Metric | Purpose |
|---|---|
| Failed logins | Volume/pattern indicating credential-stuffing or brute-force activity |
| Account lockouts | Volume, indicating either attack activity or a usability friction point worth investigating |
| MFA adoption | Percentage of accounts (especially privileged/high-integrity roles) with MFA enabled |
| Privileged accounts | Count and trend of privileged-category grants (Section, [authorization-and-privileged-access-assurance.md, Section 3](authorization-and-privileged-access-assurance.md#3-privileged-access-categories)) |
| Expired/dormant accounts | Count of accounts flagged in [../01-architecture/access-review-and-revocation.md, Section 5](../01-architecture/access-review-and-revocation.md#5-dormant-account-review) awaiting review |
| Authorization denials | Volume/pattern of explicit-deny outcomes, a candidate signal for both attack activity and legitimate UX confusion |
| SoD violation attempts | Count of any detected attempt at a SOD-01–SOD-11 conflicting combination |
| Audit-event volume | Trend, by category, informing capacity planning and gap detection |
| Audit-event gaps | Detected instances of a missing expected audit event (Section, [audit-and-security-event-architecture.md, Section 3](audit-and-security-event-architecture.md#3-audit-integrity)) |
| Security-event volume | Trend, by category (Section 8, [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md)) |
| Malware-scan outcomes | Clean/infected/failed counts, trend |
| Export volume | Trend, by classification tier, informing "unusual export" detection (Section, [audit-and-security-event-architecture.md, Section 8](audit-and-security-event-architecture.md#8-security-events)) |
| Device revocations | Count and average time-to-effect |
| Backup success rate | Per [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) |
| Restore-drill outcomes | Success/failure and time-to-restore for periodic drills |
| Vulnerability counts | By severity, open vs. remediated, per [secure-development-lifecycle.md, Section 3](secure-development-lifecycle.md#3-vulnerability-management) |
| Dependency-vulnerability alerts | Open vs. remediated count |
| Incident count and severity | Trend, by category, per [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| Time-to-detect / time-to-contain | For declared incidents, once measurable |
| Access-review completion rate | Percentage of scheduled reviews (Section, [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md)) actually completed on time |
| Break-glass/impersonation invocations | Count, if either is implemented — expected to be rare; a rising trend is itself a signal worth investigating |
| AI-policy violations | Count of detected attempts to use an AI feature outside its approved boundary |

**No numeric target/threshold is set for any metric above** — every metric is a candidate signal; specific alerting thresholds are an implementation-phase tuning decision informed by real operational data.

## 2. Monitoring Boundaries (Cross-Reference)

The distinction between application logs, security logs, audit events, infrastructure logs, device logs, access logs, integration logs, queue logs, and AI logs — and the rules governing each — are defined in full in [audit-and-security-event-architecture.md, Section 9](audit-and-security-event-architecture.md#9-logging-and-monitoring-boundaries), not duplicated here.

## 3. Compliance Reporting

| Report Type | Audience | Content |
|---|---|---|
| Control-status report | Security owner, Audit owner | Status of every entry in [compliance-control-framework.md, Section 2](compliance-control-framework.md#2-control-catalog) — candidate, in-progress, implemented, tested |
| Risk-register summary | Security owner, DepEd Leadership | Open/mitigated/accepted risk counts from [security-risk-register.md](security-risk-register.md) |
| Access-review summary | Security owner | Completion status of scheduled reviews |
| Incident summary | Incident commander, DepEd Leadership | Incident count, severity distribution, resolution status |
| Policy-source verification progress | Audit owner, Privacy owner | How many [policy-source-registry.md](policy-source-registry.md) entries remain unverified placeholders vs. confirmed |
| Vulnerability status | Application owner | Open vulnerability count by severity, remediation trend |

**No specific reporting cadence or distribution list is fixed here** — these are candidate report types; frequency and audience finalization is a governance-process decision (Section, [data-governance-operating-model.md](data-governance-operating-model.md)).

## 4. Release Security Gates

Candidate gates a release passes through before deployment (no automation created here):

1. Code review completed.
2. Automated tests passing (per [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) and [security-testing-and-assurance.md](security-testing-and-assurance.md)).
3. Static analysis (Larastan, per the existing `composer.json`) passing.
4. Dependency review completed — no newly-introduced critical/high vulnerability left unaddressed.
5. Secret scan clean.
6. Migration review — any new migration reviewed against [../02-data/database-naming-and-design-standards.md](../02-data/database-naming-and-design-standards.md) and this package's data-security requirements.
7. Authorization review — new permissions/roles reviewed against [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) and SoD implications.
8. Privacy review — changes touching personal data reviewed against [privacy-by-design-architecture.md](privacy-by-design-architecture.md).
9. File-upload review — any new upload surface reviewed against [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md).
10. Audit coverage — new consequential actions have corresponding audit events.
11. Security exceptions reviewed — any deferred vulnerability/finding has a named approval and review date.
12. Backup readiness — new data categories are incorporated into the backup-coverage plan.
13. Rollback readiness — the release has a defined rollback path.
14. Incident contacts confirmed — the current Incident commander/Security owner contact path is valid.
15. Documentation updated — this Phase 0.6 package (and Phase 0.5/0.4/0.3/0.2 where relevant) reflects the change.

**No CI/CD automation is created here** — these gates are documented requirements a future pipeline enforces.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably specific metric-alerting thresholds, compliance-report cadence/distribution, and whether release-gate enforcement is automated or manually checked in the initial implementation.
