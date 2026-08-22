# PMMS Production Readiness Report

Audit date: 2026-08-21  
Status: **Not ready for production deployment**

## 1. Current Meet

One meet exists and is active: `DdOPAA Meet 2026` (ID 1). No additional meet was created. The architecture remains meet-year capable through `meets` and `meet_sports`.

## 2. Demo data removed or isolated

The production `DatabaseSeeder` excludes demo/showcase/live-score seeders. The current local database nevertheless contains records from a prior `CoachWorkflowDemoSeeder` run: one athlete, one approved coach request, and `COACH_WORKFLOW_DEMO_DSAC` / `DSAC Demo Team`. Nine unlinked users include generic `.test` role accounts and a local administrator. These were flagged, not deleted, because cleanup requires backup and operator confirmation that none are approved accounts.

## 3-6. Users and identity

An isolated full production-seed verification created 614 real, username-enabled users from 622 source provisioning records. Each account is linked one-to-one to its canonical `Person`; 27 users retain multiple sport assignments. Eight provisions have no resolvable approved sport scope and are reported as failed rather than receiving an account.

The reviewed SQL provisioning queue contains only `sport_personnel` records. It does not approve accounts for DSC, DSAC, Medical, Event Secretariat, Central ICT, Meet Manager, Top Management, or Super Administrator. Those roles remain account-assignment blockers and no substitute identities were invented.
- Duplicate canonical people by `normalized_name`: 0.
- Two unlinked user records share the display name `John Middle Eturma`; this is a user-account duplicate candidate, not proof that either account is approved.
- Pending provisioning is the main production blocker. The activation workflow now links one user to one person and activates that person's imported sport assignments.

## 7-13. Assignment coverage

| Assignment | Count |
|---|---:|
| Tournament Manager | 54 (53 pending, 1 active) |
| Assistant Tournament Manager | 3 pending |
| Track / Field Tournament Manager | 2 pending |
| Tournament Secretary | 19 pending |
| Tournament ICT | 26 pending |
| Technical Official | 538 pending |
| DSC | 18 |
| Management/TWG members | 145 across 26 teams |

Meet-sport assignments support multiple personnel per sport and multiple roles per person. Live sport scope now reads active meet-scoped assignments for managers, secretaries, ICT, and technical officials, with legacy relationships retained temporarily as compatibility fallback.

### School master data

The approved 461-school master list is integrated into production seeding by official School ID: 420 Public and 41 Private schools, with no duplicate or invalid IDs. Initial municipality and school-district values remain null and are never inferred. Re-running the seeder preserves existing location, level, address, active-state, and relationship updates. The registry exposes Assigned/Unassigned and Public/Private filters and validates that a selected school district belongs to the selected municipality.

## 14. Medical-clearance coverage

Zero clearances exist. The reusable table covers athletes and `Personnel`, with status-only policy boundaries and medical access logging. It does not yet cover a canonical `Person` who lacks a personnel row; this blocks complete TWG/personnel medical coverage.

## 15. DSAC permissions

DSAC permissions are separated in `Permission` and resolved through active DSAC team membership. DSC and municipality monitors do not inherit DSAC approval.

## 16. Event Secretariat permissions

The actual `EVENT_SECRETARIAT` team exists with six members, but no production members are linked to users. The application now implements submitted, returned, validated, official, and reopened result states; versioned signed Result Form attachments; Event Secretariat review/download/return/validate/make-official actions; audited reopening; and official-only public/medal queries. Production use remains blocked until approved Event Secretariat people activate linked accounts.

## 17-18. Account reset and forced password change

Implemented. New production users receive a hashed password from `config('pmms.accounts.default_reset_password')`, are marked `must_change_password`, and cannot access other authenticated routes until choosing a personal password. Existing activated passwords and password-change timestamps are preserved on repeated seeds. Authorized administrators/Central ICT assignments can reset a linked account to the configured initial password; reset and password-change events are audited without password values.

## 19. Coach self-registration

Fortify registration, onboarding, municipality/district/school/event selection, assignment requests, approval, and athlete enrollment are present. Coaches are not called from the production seeder. Current local coach/athlete data is explicitly demo contamination and must be cleaned after confirmation.

## 20. Authorization tests

Targeted production-provisioning tests cover one person/one user, null email, source username reuse, assignment linking, idempotent password preservation, unscoped-record refusal, forced first-login password change, and existing invitation/login compatibility. The production frontend build succeeds. Representative role-login authorization cannot be completed for DSC, DSAC, Medical, Event Secretariat, Central ICT, or Super Administrator until approved provisions for those roles exist.

## 21. Remaining configuration issues

- Inspected environment is `local`, debug enabled, timezone UTC.
- Production must deploy with `APP_ENV=production`, `APP_DEBUG=false`, correct `Asia/Manila` application timezone if operational policy requires it, secure cookies under HTTPS, production session/cache/queue/log/mail/storage/Reverb settings, backup verification, and no committed secrets.
- Approved Super Administrator identity and credential delivery have not been identified in production source data. The generic local administrator must not be treated as approval.
- Email-less production provisioning and username authentication are supported. Email invitation activation remains available only when a real email is supplied.
- `PMMS_DEFAULT_RESET_PASSWORD` must be set to a strong deployment secret before `php artisan db:seed`; the seeder fails closed when it is missing.

## 22. Production blockers

1. Sport-personnel provisioning is operational (614 linked accounts), but eight source provisions have no approved assignment scope.
2. Existing unlinked/demo accounts and demo workflow records require operator-approved cleanup.
3. Event Secretariat, DSC, DSAC, Medical, Central ICT, Meet Manager, Top Management, and Super Administrator have no approved source account provisions.
4. Versioned Result Form attachments and official-only medal aggregation are implemented; production storage, upload limits, and document-retention backup must be verified in deployment.
5. Deployment must securely configure and deliver the initial-password process; no password appears in reports or source.
6. Medical coverage cannot yet address every canonical meet person.
7. The currently scoped authorization suite and frontend build pass, but the full suite must be rerun after the remaining implementation.
8. Deployment environment security/operations checks are not satisfied by the inspected local environment.

Production readiness must not be declared until all blockers above are resolved with approved account identity/configuration and the destructive cleanup is separately authorized.
