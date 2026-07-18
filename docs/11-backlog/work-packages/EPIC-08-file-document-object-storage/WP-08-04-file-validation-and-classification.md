# WP-08-04 — File Validation and Classification

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-04 | Title | File Validation and Classification |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 69 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | EPIC-14 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements MIME-type/size/extension validation and applies WP-14-01's classification scheme to uploaded files — the actual validation logic WP-08-03's upload command delegates to before promotion.

## 3. Architecture Sources

[../../../../03-security/](../../../../03-security/).

## 4. Scope

Implement `FileValidator` (proposed): allow-listed MIME types/extensions, maximum size, and a classification-assignment step (using WP-14-01's constants once that exists — until then, a placeholder default classification).

## 5. Explicit Exclusions

Does not implement malware scanning (WP-08-08's readiness scope, not active detection); does not implement content-based deep inspection beyond MIME/extension/size checks.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-02, WP-14-01 | Hard |

## 7. Current-State Inspection

No validation logic exists.

## 8. Proposed Implementation Direction

Proposed `FileValidator::validate(UploadedFile $file): ValidationResult`, an explicit allow-list (not a deny-list) of accepted MIME types/extensions.

## 9. Database Changes

Database Changes: None (uses WP-08-02's `classification` column).

## 10. Backend Requirements

`FileValidator` service invoked by WP-08-03's upload handler before promotion.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Validation must check actual file content signature (magic bytes), not merely trust the client-supplied MIME type or file extension — a common file-upload vulnerability class.

## 15. Privacy and Data-Governance Requirements

Classification assignment is this work package's core privacy contribution — every file gets a classification from the moment it's uploaded, never left unclassified.

## 16. Audit and Activity Events

Not Applicable directly beyond WP-08-10's later wiring.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: allow-listed file type passes; disallowed type rejected; a file with a mismatched extension-vs-actual-content-type (e.g., a `.jpg` that's actually an executable) is rejected via magic-byte inspection.

## 21. Test Data Requirements

Synthetic test files of various types, not retained.

## 22. Documentation Updates

Record the validation rules and classification-assignment logic in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `FileValidator` with allow-list and magic-byte checking | WP-08-02, WP-14-01 complete | Unit tests pass |
| TASK-02 | Wire classification assignment into the validator | TASK-01 | Test confirms classification set |

## 24. Acceptance Criteria

- **AC-01:** Given a file whose extension claims one type but whose actual content signature is different, when validated, then it is rejected.
- **AC-02:** Given a successfully validated file, when promoted, then it carries an explicit classification, never left null.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-02, WP-14-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for both criteria.

## 28. Rollback and Recovery Considerations

Not Applicable — new validation logic, no prior behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-04-01 | A file with mismatched extension/content bypasses validation due to an incomplete magic-byte check | High | AC-01 explicitly tests this class of attack | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-04 — File Validation and Classification.

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
- Validate actual file content signature, never trust client-supplied MIME type or extension alone.
- Do not implement malware scanning — that is WP-08-08's readiness scope only.
```
