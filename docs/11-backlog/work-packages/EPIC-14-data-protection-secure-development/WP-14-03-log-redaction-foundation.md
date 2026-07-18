# WP-14-03 — Log Redaction Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-03 | Title | Log Redaction Foundation |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 136 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Adds mechanical redaction to the WP-13-01 structured logging pipeline so the never-log rules stop depending on developer discipline alone: known-sensitive keys and classified attributes are stripped or replaced before any record is written, closing the exact failure mode ARR-10 in the Phase 0.13 risk register warns about.

## 3. Architecture Sources

[../../../03-security/](../../../03-security/) (secure-development and audit rules), ADR-0006, ADR-0008; consumes WP-13-01 (structured channel) and WP-14-01 (classification vocabulary).

## 4. Scope

A Monolog processor (proposed: `RedactionProcessor`) applied to every channel, redacting: a maintained deny-list of keys (password, token, secret, authorization, cookie, and variants); values of attributes classified Confidential/Restricted-equivalent per WP-14-01 when models/DTOs are logged; recursive redaction through nested context arrays; a documented process for extending the deny-list.

## 5. Explicit Exclusions

Does not implement display masking (WP-14-02 — different pipeline); does not sanitize responses (WP-13-08 owns error rendering); does not attempt semantic detection of unlabeled sensitive content (pattern-guessing PII is out of Phase 1 scope — key- and classification-based redaction only, with the limitation documented).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-01 | Hard |
| WP-14-01 | Hard |

## 7. Current-State Inspection

WP-13-01 establishes the structured channel with normative never-log conventions but no mechanical enforcement.

## 8. Proposed Implementation Direction

Processor registered globally in the logging configuration; deny-list in one configuration file (proposed: `config/logging-redaction.php`); replacement token `"[REDACTED]"`; recursion depth-limited with fail-closed truncation.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Processor, deny-list configuration, classification integration, extension process documentation.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable — WP-12-11's mobile redaction guard is the mobile counterpart.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Redaction must apply to every channel (including fallback/emergency channels); on processor failure, the record's context is dropped entirely rather than written unredacted (fail closed); deny-list matching is case-insensitive and matches key substrings conservatively.

## 15. Privacy and Data-Governance Requirements

Classified-attribute redaction is the mechanical safeguard for personal data in logs; the semantic-detection limitation (Section 5) is documented so nobody assumes protection that does not exist.

## 16. Audit and Activity Events

Not Applicable — audit payload masking is governed by WP-06-05/WP-06-08 rules; this work package covers application logs.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queue-worker logs pass through the same processor (single global registration).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Redaction must not break record structure — redacted records remain valid JSON with all standard fields.

## 20. Testing Requirements

Tests: each deny-list category redacted (flat and nested); classified attribute redacted when a model/DTO is logged; processor failure drops context rather than leaking (simulated); redacted records remain schema-valid; queue-worker path covered.

## 21. Test Data Requirements

Synthetic sensitive-looking values only.

## 22. Documentation Updates

Record the deny-list location, extension process, and the semantic-detection limitation in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement redaction processor with deny-list | WP-13-01 complete | Category tests pass |
| TASK-02 | Integrate WP-14-01 classification redaction | WP-14-01 complete | Classified-attribute test passes |
| TASK-03 | Verify fail-closed and all-channel coverage | TASK-01 | Failure and channel tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a log call containing any deny-listed key at any nesting depth, when written, then its value is `[REDACTED]`.
- **AC-02:** Given a logged model/DTO with Confidential/Restricted-classified attributes, when written, then those attribute values are redacted.
- **AC-03:** Given a simulated processor failure, when the record is written, then its context is dropped — no unredacted content reaches the log.
- **AC-04:** Given every configured channel including fallbacks, when inspected, then the processor is active.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-01 and WP-14-01 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): before/after record captures, test output, channel-coverage listing.

## 28. Rollback and Recovery Considerations

Processor removal restores prior behavior; no data migration. Existing log files are unaffected (redaction is forward-only — noted for retention handling).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-03-01 | Sensitive value logged under a novel key name that matches no deny-list entry and no classification | High | Documented limitation; conventions (WP-13-01) remain normative; WP-14-10 adds category-sweep regressions; deny-list extension process is lightweight | Security reviewer |
| RISK-WP-14-03-02 | Over-broad matching redacts diagnostic essentials, eroding trust in logs | Low | Conservative substring rules + tests asserting non-sensitive keys survive | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-03 — Log Redaction Foundation.

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
- Fail closed: on processor failure, drop context — never write unredacted content.
- The processor must cover every channel, including fallback channels.
- Document the semantic-detection limitation explicitly — do not claim protection beyond key/classification matching.
```
