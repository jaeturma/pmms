# WP-09-09 — Reverb and Broadcast Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-09 | Title | Reverb and Broadcast Baseline |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 85 |
| Target release group | Foundation Release D | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | DevOps lead |

## 2. Purpose

Installs and configures Laravel Reverb, switching `BROADCAST_CONNECTION` from `log` to `reverb`, giving PMMS its first real-time WebSocket capability — required by the approved technology direction but entirely absent from the current starter kit.

## 3. Architecture Sources

Approved technology direction (Laravel Reverb), ADR-0004.

## 4. Scope

Install `laravel/reverb` via Composer (`php artisan install:broadcasting`); configure `config/reverb.php`/`config/broadcasting.php`; switch `BROADCAST_CONNECTION=reverb`; confirm a test event broadcasts and is receivable by a test client.

## 5. Explicit Exclusions

Does not implement any concrete broadcast channel for a real capability (no such capability exists yet); does not implement private-channel authorization (WP-09-10's scope).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

`composer.json` does not include `laravel/reverb`; `BROADCAST_CONNECTION=log` in `.env.example`.

## 8. Proposed Implementation Direction

Standard Reverb installation; a test public channel and event to confirm end-to-end connectivity before any private-channel work begins.

## 9. Database Changes

Database Changes: None (Reverb is stateless/transient by design, per ADR-0004/ADR-0012).

## 10. Backend Requirements

Reverb package installed; `BROADCAST_CONNECTION=reverb`.

## 11. Web Frontend Requirements

Laravel Echo configured in `resources/js/` (proposed) for connecting to Reverb from React.

## 12. Flutter Requirements

Not Applicable in this work package — Flutter real-time connectivity is a future EPIC-12 enhancement beyond the Phase 1 foundation.

## 13. Authorization and Access Control

Not Applicable in this work package — this is public-channel only; private channels are WP-09-10.

## 14. Security Requirements

Confirm Reverb server itself requires appropriate authentication for its own admin/stats endpoints (Reverb's own configuration, not application-level).

## 15. Privacy and Data-Governance Requirements

Reverb never becomes authoritative for any data — restated from ADR-0004/ADR-0012, applying to this first concrete implementation.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package **is** the real-time foundation every later broadcast-dependent capability builds on.

## 18. Offline and Synchronization Requirements

Not Applicable directly — reconnection/state-refresh is WP-09-11's scope.

## 19. Observability Requirements

Reverb connectivity feeds WP-13-06's health signal.

## 20. Testing Requirements

Integration test confirming a test event broadcasts on a public channel and is receivable.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the Reverb installation and configuration in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Install and configure Reverb | WP-01-01 complete | Package installed |
| TASK-02 | Configure Laravel Echo in the frontend | TASK-01 | Echo connects |
| TASK-03 | Test end-to-end public-channel broadcast | TASK-01..02 | Test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a test event broadcast on a public channel, when a connected client listens, then it receives the event.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Reverting `BROADCAST_CONNECTION` to `log` is possible if Reverb proves problematic.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-09-01 | No Reverb-compatible environment (WebSocket support) is available in the execution environment | Medium | Flagged as an environment precondition, same discipline as WP-08-01/WP-09-01 | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-09 — Reverb and Broadcast Baseline.

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
- Do not implement any private channel or channel for a real capability — public test channel only.
- Reverb must never be treated as authoritative for any data.
```
