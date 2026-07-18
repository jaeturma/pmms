# WP-13-01 — Structured Logging Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-01 | Title | Structured Logging Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 124 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Replaces default single-line text logging with structured (JSON) application logging carrying consistent fields (timestamp, level, channel, correlation ID, context), so every later module's log output is machine-parseable and diagnosable — the web-side counterpart of WP-12-11's mobile logging, per ADR-0008's observability architecture.

## 3. Architecture Sources

[../../../05-devops/](../../../05-devops/) (observability architecture), [../../../01-architecture/observability-and-error-handling.md](../../../01-architecture/observability-and-error-handling.md), ADR-0008.

## 4. Scope

Configure a structured JSON log channel as the application default (proposed: Monolog `JsonFormatter` on the `stack` channel); define the standard log-record fields (timestamp, level, message, channel, correlation ID once WP-13-02 exists, sanitized context array); document logging conventions (when to log at which level, what belongs in context, what must never be logged); ensure queue workers and scheduled commands log through the same structured channel.

## 5. Explicit Exclusions

Does not implement log redaction rules (WP-14-03 consumes this foundation); does not implement error reporting/aggregation (WP-13-08); does not select or integrate an external log-aggregation or APM service (a future, separately-authorized operational decision); does not create production logging infrastructure.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-08 | Hard |

## 7. Current-State Inspection

The starter kit uses Laravel's default `stack`/`single` channel with plain-text formatting; no structured fields, no correlation ID, no documented conventions.

## 8. Proposed Implementation Direction

`config/logging.php` structured channel configuration (proposed); a thin logging-conventions document; a context-processor that appends correlation/context fields to every record (integrating with WP-02-08's request-context foundation).

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Structured channel configuration; a Monolog processor appending standard fields; conventions documentation. No new domain code.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable — WP-12-11 owns the mobile counterpart.

## 13. Authorization and Access Control

Not Applicable directly — log file/stream access is an operational concern documented in the conventions, not an application authorization surface in Phase 1.

## 14. Security Requirements

Conventions must state explicitly that credentials, tokens, session identifiers, and full request bodies are never logged; enforcement tooling arrives with WP-14-03 (log redaction), but the rule is normative from this work package onward.

## 15. Privacy and Data-Governance Requirements

Conventions must prohibit logging personal data (names, birthdates, medical or guardian information) in log context; WP-14-03 adds mechanical redaction on top of this rule.

## 16. Audit and Activity Events

Not Applicable — application logging is explicitly distinct from the audit system per WP-06-01's three-way distinction; this work package's conventions restate that distinction.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queue workers and scheduled commands must emit through the same structured channel so Horizon-processed jobs are diagnosable with the same fields.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

This work package is the web-side observability foundation; WP-13-02 (correlation), WP-13-08 (safe error reporting), and WP-14-03 (redaction) all build on its channel and conventions.

## 20. Testing Requirements

Test asserting a log record emitted through the default channel is valid JSON containing the standard fields; test asserting a queued job's log output carries the same structure.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the logging conventions (levels, context rules, never-log list) in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Configure structured JSON default channel | WP-02-08 complete | Log records are valid JSON |
| TASK-02 | Implement standard-fields processor | TASK-01 | Fields present on every record |
| TASK-03 | Verify queue/scheduler output uses the channel | TASK-02 | Job log record carries standard fields |
| TASK-04 | Write logging conventions documentation | TASK-01 | Conventions recorded per Section 22 |

## 24. Acceptance Criteria

- **AC-01:** Given any log call through the default channel, when the record is written, then it is valid JSON containing timestamp, level, message, and channel fields.
- **AC-02:** Given a queued job that logs, when it is processed, then its log records carry the same structured fields as HTTP-request logs.
- **AC-03:** Given the conventions document, when reviewed, then it explicitly lists the never-log categories (credentials, tokens, session IDs, personal data).

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-02-08 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): sample structured log records (HTTP and queued), test output, conventions document.

## 28. Rollback and Recovery Considerations

Configuration-only change; reverting `config/logging.php` restores default behavior. No data migration.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-01-01 | Later modules log sensitive data into structured context before WP-14-03's redaction exists | High | Conventions rule is normative immediately; WP-14-03 and WP-14-10 verify mechanically; EPIC-14 starts in parallel per the execution sequence | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-13-01-01 | External log-aggregation/APM service selection | Non-blocking for Phase 1 — local structured logs only |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-01 — Structured Logging Foundation.

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
- Never log credentials, tokens, session identifiers, or personal data.
- Do not integrate an external log-aggregation or APM service.
- Application logging must remain distinct from the audit system.
```
