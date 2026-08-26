# Test Alignment Review — 2026-08-26

## Summary

The six failures were reviewed against the current PMMS workflow. Five are stale test setup or request expectations. The password-reset failure also exposes a production defect: reset endpoints use the configured password but do not require the user to change it afterward.

| # | Test | Error | Recent rule causing failure | Test stale? | Production bug? | Required fix | Security implications |
|---|---|---|---|---|---|---|---|
| 1 | `CoachAccountTest`: a coach can manually build a team event from their approved athletes | Coach may select only accredited athletes | Entry membership requires an actual accreditation record, not eligibility approval alone | Yes | No | Create normal athlete accreditation records after approved eligibility | Preserves the barrier against entering athletes who passed screening but were not accredited |
| 2 | `CoachAccountTest`: a coach can submit and withdraw an entry but cannot confirm it | Only accredited athletes may be submitted | Coach entry submission requires actual accreditation | Yes | No | Accredit the fixture athlete through the domain data model before submission | Preserves accreditation enforcement and the coach/official confirmation boundary |
| 3 | `CoachEnrollmentWorkflowTest`: approved coach scope limits registration... | `school_id` is prohibited | Enrollment derives school/delegation context and initial requests are sport-scoped | Yes | No | Remove client-controlled school/event fields and align the fixture with the current request contract and approval flow | Prevents privilege/scope injection through untrusted IDs |
| 4 | `CoachRegistrationAssignmentAlignmentTest`: tournament ICT reviews only its sport... | Coach profile photo required | Coach approval requires a valid profile upload and at least one assigned event | Yes | No | Add a `FileUpload` profile fixture and submit `event_ids` as an array | Preserves coach identity evidence and scoped approval |
| 5 | `DdOPAA2026FinalImportTest`: requesting coach onboarding does not self grant authority | `school_id` is prohibited | Registration selects sport; trusted context and events are established later | Yes | No | Remove prohibited school/event request data while retaining the no-self-grant assertions | Preserves onboarding mass-assignment protection |
| 6 | `UserManagementTest`: tournament secretary can see registrations and reset password | Reset hash does not match hard-coded legacy password | Password comes from configuration; system-account reset belongs to Central ICT/admin; reset must force password change | Yes | Yes | Assert secretary view-only/forbidden reset, perform reset as an authorized account administrator, assert configured password and `must_change_password`; fix reset endpoints to set the flag | Avoids over-permission, secret coupling, and continued use of an administrator-known temporary password |

## Role review

The current role matrix scopes Tournament Secretary to operational visibility. Coach review/reset authorization is restricted to Tournament ICT for sport-scoped coach workflows, while system user management is restricted by `canManageProductionAccounts()`. Tests must not restore Tournament Secretary password-reset authority.

## Production changes permitted by this review

Only the forced-password-change defect and user-facing reset messages that expose a hard-coded password should be changed in production. Accreditation, profile-photo, trusted-context, event-scope, and authorization validations must remain intact.
