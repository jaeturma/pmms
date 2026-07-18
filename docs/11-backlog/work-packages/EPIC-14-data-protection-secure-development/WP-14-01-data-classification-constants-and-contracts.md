# WP-14-01 — Data Classification Constants and Contracts

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-01 | Title | Data Classification Constants and Contracts |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 134 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting (shared kernel) |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Turns the Phase 0.6 information-classification tiers into code-level constants and contracts (a classification enum plus an interface for declaring an attribute's classification), giving masking (WP-14-02), redaction (WP-14-03), export (WP-14-04), file classification (WP-08-04), and prop minimization (WP-10-04) one shared vocabulary instead of five ad-hoc ones.

## 3. Architecture Sources

[../../../02-data/information-classification-and-privacy.md](../../../02-data/information-classification-and-privacy.md), [../../../03-security/](../../../03-security/) (data-classification rules), ADR-0006. Note: SD-09 (classification-tier formal validation) remains open; the tiers implemented here mirror the documented architecture and are structured to absorb a validated revision.

## 4. Scope

A classification enum in the shared kernel (proposed: `App\SharedKernel\Classification\DataClassification` with the documented tiers — Public, Internal, Confidential, Restricted, or the exact tier names from the Phase 0.6 corpus verified at implementation); a contract for classified attributes (proposed: interface declaring per-attribute classification on models/DTOs); documented guidance on classifying new attributes; classification of the attributes that exist in Phase 1 foundation models (user, organization, meet, membership, file metadata).

## 5. Explicit Exclusions

Does not implement masking, redaction, or export behavior (WP-14-02/03/04 consume the constants); does not classify future sports/medical/finance module data (those modules classify their own attributes when built, using this vocabulary); does not resolve SD-09 — tier names follow the documented architecture pending formal validation.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

No classification vocabulary exists in code; the starter kit's models carry no classification metadata.

## 8. Proposed Implementation Direction

Shared-kernel enum + interface (proposed names above, verified against the Phase 0.6 tier names at implementation); attribute classification maps on existing foundation models.

## 9. Database Changes

Database Changes: None — classification lives in code contracts, not columns, in Phase 1.

## 10. Backend Requirements

Enum, contract, guidance document, classification maps on Phase 1 foundation models.

## 11. Web Frontend Requirements

Not Applicable directly — WP-10-04 consumes classifications server-side.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — classification informs, but does not replace, authorization decisions.

## 14. Security Requirements

The vocabulary must be single-sourced in the shared kernel — no consumer may define its own tier list; unclassified attributes on classified-aware models default to the most restrictive treatment consumers apply (fail-closed).

## 15. Privacy and Data-Governance Requirements

This work package is the code anchor for the Phase 0.6 privacy architecture; minor-athlete and medical categories (when those modules arrive) map onto the Restricted-equivalent tier per the documented governance.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly — WP-14-03 applies the vocabulary to logging.

## 20. Testing Requirements

Unit tests: enum tiers match the documented architecture; foundation-model classification maps exist and are complete (every fillable attribute classified or explicitly exempted); fail-closed default verified in the contract's helper behavior.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the vocabulary, contract, and classification guidance in `.ai/architecture.md` addendum; cross-reference `.ai/data-classification-rules.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement classification enum and contract in shared kernel | WP-02-02 complete | Unit tests pass |
| TASK-02 | Classify Phase 1 foundation model attributes | TASK-01 | Completeness test passes |
| TASK-03 | Write classification guidance | TASK-01 | Guidance recorded per Section 22 |

## 24. Acceptance Criteria

- **AC-01:** Given the shared kernel, when inspected, then exactly one classification vocabulary exists and matches the documented Phase 0.6 tiers.
- **AC-02:** Given every Phase 1 foundation model, when its classification map is checked, then every fillable attribute is classified or explicitly exempted with a reason.
- **AC-03:** Given an unclassified attribute on a classification-aware consumer, when processed, then it is treated at the most restrictive tier (fail-closed).

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): enum/contract review, completeness test output.

## 28. Rollback and Recovery Considerations

Code-only contracts; no data migration. Consumers not yet built.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-01-01 | SD-09 validation later renames/restructures tiers, rippling through consumers | Medium | Single-sourced enum means one change site; consumers reference the enum, never string literals | Security reviewer |
| RISK-WP-14-01-02 | A later model skips its classification map, silently opting out | High | WP-14-10 adds an architecture test asserting classification-awareness on models touching personal data | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-14-01-01 | SD-09 — formal classification-tier validation | Non-blocking — documented tiers implemented, single change site preserved |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-01 — Data Classification Constants and Contracts.

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
- One vocabulary, single-sourced in the shared kernel — verify tier names against the Phase 0.6 corpus before coding.
- Fail-closed: unclassified means most restrictive.
- Constants and contracts only — no masking/redaction/export behavior here.
```
