# WP-07-08 — Configuration Classification and Validation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-08 | Title | Configuration Classification and Validation |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 62 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Distinguishes application-level configuration (`.env`/`config/*.php`, deployment-time) from reference data (`organization_types`/`sports`/etc., runtime-editable business data) — a boundary Phase 0.13's data review flagged as needing clarity — and documents validation rules for each configuration category.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/), ADR-0005.

## 4. Scope

Document the configuration-vs-reference-data boundary explicitly; classify existing `config/*.php` values by sensitivity (public/internal/secret) per a lightweight scheme that WP-14-01 will later formalize fully.

## 5. Explicit Exclusions

Does not implement a runtime configuration-management UI (feature flags are WP-07-09's narrower scope); does not implement WP-14-01's full data-classification model — this work package's classification is configuration-specific only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

`config/*.php` files (app, auth, cache, database, filesystems, fortify, inertia, logging, mail, queue, services, session) — none currently classified by sensitivity.

## 8. Proposed Implementation Direction

Documentation-only classification pass over existing `config/*.php`, flagging secret-bearing config paths (e.g., `services.php`'s API keys) for WP-01-06/WP-14-08's secret-hygiene checks to reference.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable — documentation only.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Identifies which configuration paths carry secrets, feeding WP-14-08's hygiene checks.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable — documentation only.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the configuration-vs-reference-data boundary and the classification pass in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document the configuration-vs-reference-data boundary | WP-02-02 complete | Documented |
| TASK-02 | Classify existing `config/*.php` values by sensitivity | TASK-01 | Classification documented |

## 24. Acceptance Criteria

- **AC-01:** Given the boundary documentation, when reviewed, then it clearly distinguishes deployment-time configuration from runtime reference data.
- **AC-02:** Given the classification pass, when reviewed, then every secret-bearing config path is flagged.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented boundary and classification.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-08-01 | A secret-bearing config path is missed during classification | Medium | WP-14-08 performs an independent secret-hygiene check | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-08 — Configuration Classification and Validation.

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
- Documentation only — no config file modification.
```
