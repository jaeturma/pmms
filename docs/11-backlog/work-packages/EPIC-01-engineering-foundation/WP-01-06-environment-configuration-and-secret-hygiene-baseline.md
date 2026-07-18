# WP-01-06 — Environment Configuration and Secret Hygiene Baseline

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-01-06 |
| Title | Environment Configuration and Secret Hygiene Baseline |
| Epic | EPIC-01 — Engineering Foundation and Repository Baseline |
| Phase | Phase 1 |
| Status | Planned — Not Started |
| Complexity | Small |
| Priority | P1 |
| Implementation sequence | 6 |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints |
| Primary bounded context | None (cross-cutting) |
| Secondary affected contexts | EPIC-08 (storage), EPIC-09 (queue/Redis) |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | DevOps lead, Security reviewer |

## 2. Purpose

Reviews `.env.example` against the approved technology direction (MySQL, Redis, MinIO/S3-compatible storage, Reverb) and documents the gap between the current SQLite/database-queue/log-broadcast defaults and the target configuration, without changing deployment topology (DV-01, still open and non-blocking per Section 16 of the main backlog document).

## 3. Architecture Sources

[../../../../05-devops/](../../../../05-devops/), [../../../10-review/devops-observability-operations-and-recovery-review.md](../../../10-review/devops-observability-operations-and-recovery-review.md).

## 4. Scope

Document the current `.env.example` defaults (`DB_CONNECTION=sqlite`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `BROADCAST_CONNECTION=log`, `SESSION_DRIVER=database`); propose target local-development values (`DB_CONNECTION=mysql`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `BROADCAST_CONNECTION=reverb`, S3-compatible values for MinIO); confirm no secret value is committed anywhere in the repository (`git log`, `.env` in `.gitignore`).

## 5. Explicit Exclusions

Does not modify `.env.example` itself (a later work package, once DEC-GENERAL-02/03 are actually acted on, may); does not provision MySQL/Redis/MinIO services; does not resolve DV-01 deployment topology.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |
| DV-01 (deployment topology) | Soft — does not block this work package's documentation scope |

## 7. Current-State Inspection

Inspect `.env.example` (confirmed at authoring time: `DB_CONNECTION=sqlite`, `REDIS_CLIENT=phpredis`/`REDIS_HOST`/`REDIS_PORT` present but unused by default connections, `AWS_*` variables present but empty, `BROADCAST_CONNECTION=log`), `.gitignore` (confirm `.env` excluded), `config/database.php`, `config/queue.php`, `config/cache.php`, `config/filesystems.php` (s3 disk already defined with `AWS_ENDPOINT`/`AWS_USE_PATH_STYLE_ENDPOINT` supporting MinIO-compatible configuration).

## 8. Proposed Implementation Direction

Documentation-only. Record the gap and proposed target values in `.ai/engineering-baseline.md`; defer actually changing `.env.example` to whichever work package first requires MySQL/Redis/MinIO connectivity (WP-08-01, WP-09-01).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable (documentation only).

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Confirm `.env` is git-ignored; confirm no credential appears in `.env.example`, `composer.json`, `package.json`, or Git history.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Documents that `QUEUE_CONNECTION` and `BROADCAST_CONNECTION` must move to `redis` and `reverb` respectively before EPIC-09 begins — recorded as a soft dependency for WP-09-01/WP-09-09, not implemented here.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable at this stage — EPIC-13 builds observability.

## 20. Testing Requirements

No automated tests; configuration review only.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Extend `.ai/engineering-baseline.md` with the environment-configuration gap analysis and proposed target values.

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Review `.env.example` against target technology direction | `.env.example` (read-only) | WP-01-01 complete | Gap list produced | — |
| TASK-02 | Confirm `.env` is git-ignored and no secret exists in tracked files | `.gitignore`, `git log` | None | Confirmed | — |
| TASK-03 | Review `config/database.php`, `config/queue.php`, `config/cache.php`, `config/filesystems.php` for MySQL/Redis/MinIO readiness | `config/*.php` (read-only) | None | Findings recorded | s3 disk already MinIO-compatible per Section 7 |
| TASK-04 | Append findings to `.ai/engineering-baseline.md` | `.ai/engineering-baseline.md` | TASK-01..03 complete | File updated | — |

## 24. Acceptance Criteria

- **AC-01:** Given `.env.example`, when reviewed, then every gap between current defaults and the target technology direction is listed with a proposed target value.
- **AC-02:** Given `.gitignore` and `git log`, when checked, then it is confirmed no secret value is committed, or any finding is flagged as Critical.
- **AC-03:** Given `config/filesystems.php`, when reviewed, then its existing MinIO-compatibility (via `AWS_ENDPOINT`/`AWS_USE_PATH_STYLE_ENDPOINT`) is documented as already present.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 must be Implementation Complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Environment gap analysis complete and recorded; no secret-exposure finding left unresolved without explicit sign-off.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the gap-analysis table itself, plus confirmation output for the secret-hygiene check.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation-only change.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-01-06-01 | A secret is discovered committed in Git history | Critical | Immediate escalation, not silent remediation | TASK-02 | Security reviewer | Depends on finding, if any |

## 30. Open Decisions

| Decision ID | Question | Recommended Direction | Blocking Status |
|---|---|---|---|
| DEC-WP-01-06-01 | Should Docker Compose provision MySQL/Redis/MinIO for local development? | See DEC-GENERAL-03 in [../../../phase-1-decision-register.md](../../../phase-1-decision-register.md) | Non-blocking |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-01-06 — Environment Configuration and Secret Hygiene Baseline.

Read the complete work-package document first.

Inspect the current repository before making changes.

Implement only the approved scope.

Do not implement excluded or deferred features.

Follow all linked architecture, security, privacy, testing, design, workflow, and operational rules.

Run the required tests and quality checks.

Update the required documentation and AI workspace files.

Do not commit unless explicitly instructed.

At completion, provide:
1. Repository findings
2. Files created
3. Files modified
4. Implementation summary
5. Database changes
6. Backend changes
7. Frontend changes
8. Flutter changes
9. Authorization and audit changes
10. Tests and quality checks
11. Risks and limitations
12. Git status

Additional restrictions specific to this work package:
- Do not modify .env.example or any config/*.php file.
- Escalate immediately, do not silently remediate, if a committed secret is found.
```
