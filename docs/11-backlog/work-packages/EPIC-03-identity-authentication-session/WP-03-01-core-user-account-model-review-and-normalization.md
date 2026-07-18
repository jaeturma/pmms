# WP-03-01 — Core User Account Model Review and Normalization

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-01 | Title | Core User Account Model Review and Normalization |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 17 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Reviews the existing starter-kit `User` model (`app/Models/User.php`) against PMMS's identity model and normalizes it into the WP-02-01 namespace convention and WP-02-05 repository pattern, without discarding the working Fortify/2FA/passkey behavior WP-01-04 verified.

## 3. Architecture Sources

[../../../../01-architecture/identity-model.md](../../../../01-architecture/identity-model.md), ADR-0003.

## 4. Scope

Review `User` model's current fields/relationships; add PMMS-required fields not yet present (e.g., account status, per identity-model.md); introduce a `UserRepositoryInterface` per WP-02-05; preserve existing Fortify contract compatibility (2FA columns, passkeys relationship).

## 5. Explicit Exclusions

Does not implement organization membership (WP-04-03) or role/permission assignment (EPIC-05) — those reference this normalized model but are built later; does not remove any existing Fortify-required column.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-01, WP-02-05 | Hard |
| WP-01-04 (authentication baseline verified) | Soft |

## 7. Current-State Inspection

`app/Models/User.php` (Fortify-standard fields, 2FA columns via `2025_08_14_170933_add_two_factor_columns_to_users_table.php`, passkeys relationship via `2024_01_01_000000_create_passkeys_table.php`), `database/factories/UserFactory.php`.

## 8. Proposed Implementation Direction

Keep `User` as the Eloquent model (Fortify requires this); add a domain-layer `UserAccount` aggregate (proposed) in `App\Domain\Identity\` that wraps identity-model concepts not native to Fortify (e.g., account status); introduce `App\Domain\Identity\UserRepositoryInterface` implemented by `App\Infrastructure\Identity\EloquentUserRepository`.

## 9. Database Changes

Proposed additions to the existing `users` table (via a new migration, not modifying the original starter-kit migration): an `account_status` column (proposed enum: active/suspended/revoked, default active) per WP-03-04's needs. Ownership: Identity/Access context. No new unique constraint beyond the existing `email` uniqueness. History requirement: status changes recorded via WP-06-01's audit foundation, not a status-history table in Phase 1.

## 10. Backend Requirements

Domain: `UserAccount` wraps `User` Eloquent model; Application: no new command yet (status transitions are WP-03-04's scope); Authorization: none yet (EPIC-05); Validation: `account_status` enum validated at the database and application layer; Transactions: status changes wrapped in `DB::transaction()` per WP-02-05.

## 11. Web Frontend Requirements

Not Applicable — no UI change in this work package.

## 12. Flutter Requirements

Not Applicable directly — WP-12-05 later consumes the normalized model via the API contract.

## 13. Authorization and Access Control

Not Applicable — authorization model does not exist yet at this point in the sequence.

## 14. Security Requirements

Confirm the added `account_status` column does not weaken existing password-hash or 2FA-secret column protections.

## 15. Privacy and Data-Governance Requirements

`account_status` is not sensitive; no new classification concern introduced.

## 16. Audit and Activity Events

Not implemented here — WP-03-07 wires authentication audit; this work package only ensures the model has a hook point for it.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Feature tests confirming existing Fortify tests (`tests/Feature/Auth/*`) still pass unmodified after normalization; unit test for the new `UserRepositoryInterface` implementation.

## 21. Test Data Requirements

Reuses existing `UserFactory`; extend it (not create anew) with an `account_status` state if needed. Do not create test data beyond factory extension.

## 22. Documentation Updates

Update `.ai/architecture.md` with the Identity context's model structure; note in `docs/01-architecture/identity-model.md`'s cross-reference (via this work package's own document, not by editing the Phase 0 source).

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Review `User` model against identity-model.md requirements | WP-02-01, WP-02-05 complete | Gap list produced |
| TASK-02 | Design `account_status` column addition (migration proposal) | TASK-01 | Migration proposal documented |
| TASK-03 | Design `UserRepositoryInterface`/`EloquentUserRepository` | WP-02-05 complete | Interface documented |
| TASK-04 | Confirm existing Fortify tests unaffected by proposed changes | TASK-01..03 | Test-impact analysis documented |

## 24. Acceptance Criteria

- **AC-01:** Given the existing `tests/Feature/Auth/*` suite, when the normalization is implemented, then every existing test still passes unmodified.
- **AC-02:** Given the new `account_status` column, when added, then it defaults to `active` and does not break any existing user record.
- **AC-03:** Given `UserRepositoryInterface`, when implemented, then domain code never references the Eloquent `User` model directly.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-01 and WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Existing Fortify tests pass unmodified; migration reversible.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test results before/after, migration status, gap-analysis findings.

## 28. Rollback and Recovery Considerations

The new `account_status` migration must be reversible (`down()` drops the column); no existing column is altered destructively.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-01-01 | Normalization inadvertently breaks passkey or 2FA relationship | High | AC-01 requires full existing test-suite pass before merge | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-01 — Core User Account Model Review and Normalization.

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
- Do not break any existing Fortify/2FA/passkey test.
- Do not remove any existing User column.
```
