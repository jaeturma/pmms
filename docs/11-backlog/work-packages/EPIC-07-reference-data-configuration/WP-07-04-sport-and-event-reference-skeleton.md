# WP-07-04 — Sport and Event Reference Skeleton

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-04 | Title | Sport and Event Reference Skeleton |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 58 |
| Target release group | Foundation Release C | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | BC-10 (deferred) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer, Sports-domain representative |

## 2. Purpose

Implements a bare `sports` reference table (name only — e.g., "Track and Field," "Basketball") as a picklist skeleton, explicitly without any scoring rule, event structure, or eligibility criterion, since OD-10/PSG-04/14 (sports rule source) remain unverified and the actual Sports Catalog/Competition Entries module is excluded from Phase 1.

## 3. Architecture Sources

[../../../../01-architecture/domain-open-decisions.md](../../../../01-architecture/domain-open-decisions.md) (OD-10), [../../../10-review/policy-rulebook-and-source-validation-gap-register.md](../../../10-review/policy-rulebook-and-source-validation-gap-register.md) (PSG-04, PSG-14).

## 4. Scope

Implement `sports` table per WP-07-01's pattern (code, label, active, sort_order only); seed with commonly-known sport names, explicitly labeled as names only — no rule content.

## 5. Explicit Exclusions

Does not implement events, brackets, scoring rules, or eligibility criteria for any sport; does not implement the Sports Catalog/Competition Entries module (BC-10/11, out of Phase 1 scope per Section 5).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01 | Hard |
| OD-10, PSG-04, PSG-14 | Deferred — this work package does not require their resolution, since it stores only names |

## 7. Current-State Inspection

No sport reference table exists.

## 8. Proposed Implementation Direction

`sports` table per WP-07-01's pattern; explicitly no `rules`, `scoring_method`, or `eligibility_criteria` column — a bare picklist only.

## 9. Database Changes

Proposed `sports` table: `id`, `code`, `label`, `active`, `sort_order`, timestamps — no rule-bearing columns.

## 10. Backend Requirements

Seeder only.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Seeder test confirming names-only content, and a schema test asserting no rule-bearing column exists (a structural regression guard against future scope creep).

## 21. Test Data Requirements

The seeder is provisional reference data (names only).

## 22. Documentation Updates

Record the names-only boundary explicitly in `.ai/architecture.md` addendum, cross-referencing OD-10/PSG-04/14.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `sports` migration (names-only schema) and seeder | WP-07-01 complete | Schema test confirms no rule column |

## 24. Acceptance Criteria

- **AC-01:** Given the `sports` table, when its schema is inspected, then it contains no scoring-rule, eligibility, or event-structure column.
- **AC-02:** Given the seeded data, when reviewed, then every entry is a bare name, with no embedded rule content in any field.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): schema-test output, seed content review.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder idempotent.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-04-01 | A future work package, under time pressure, adds a rule-bearing column to this table instead of building a proper Sports Catalog module | High | AC-01's schema test is a standing regression guard | Sports-domain representative |

## 30. Open Decisions

None (OD-10/PSG-04/14 tracked externally, non-blocking for this skeleton).

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-04 — Sport and Event Reference Skeleton.

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
- This table must contain names only — no scoring rule, eligibility criterion, or event structure, ever, until a dedicated future phase with verified sport-rule sources builds the real Sports Catalog module.
```
